<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Member;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MemberControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_index_returns_paginated_members(): void
    {
        Member::factory()->count(20)->create();

        $response = $this->getJson('/api/members');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'rows',
                'counts' => [
                    'types',
                    'total_rows',
                    'total_pages',
                ],
            ]);
    }

    public function test_index_filters_by_type(): void
    {
        Member::factory()->create(['type' => 'permanent']);
        Member::factory()->create(['type' => 'guest']);

        $response = $this->getJson('/api/members?type=permanent');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('counts.total_rows'));
    }

    public function test_index_searches_by_name(): void
    {
        Member::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Member::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $response = $this->getJson('/api/members?search=John');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('counts.total_rows'));
    }

    public function test_list_returns_id_and_name_only(): void
    {
        Member::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $response = $this->getJson('/api/members/list');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name'],
            ]);

        $this->assertArrayNotHasKey('email', $response->json()[0]);
    }

    public function test_list_filters_by_name(): void
    {
        Member::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Member::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $response = $this->getJson('/api/members/list?search=John');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals('John Doe', $response->json()[0]['name']);
    }

    public function test_list_limits_to_30_members(): void
    {
        Member::factory()->count(50)->create();

        $response = $this->getJson('/api/members/list');

        $response->assertStatus(200);
        $this->assertCount(30, $response->json());
    }

    public function test_store_creates_member(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'mobile' => '0501234567',
            'type' => 'permanent',
        ];

        $response = $this->postJson('/api/members', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['first_name' => 'John']);

        $this->assertDatabaseHas('members', ['email' => 'john@example.com']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/members', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'type']);
    }

    public function test_store_validates_unique_member_number(): void
    {
        Member::factory()->create(['member_number' => 'MEM-001']);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'type' => 'permanent',
            'member_number' => 'MEM-001',
        ];

        $response = $this->postJson('/api/members', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['member_number']);
    }

    public function test_show_returns_member_details(): void
    {
        $member = Member::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $response = $this->getJson("/api/members/{$member->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['firstName' => 'John'])
            ->assertJsonStructure([
                'id',
                'firstName',
                'lastName',
                'fullName',
                'mobile',
                'email',
                'type',
                'groups',
            ]);
    }

    public function test_show_returns_404_for_nonexistent_member(): void
    {
        $response = $this->getJson('/api/members/99999');

        $response->assertStatus(404);
    }

    public function test_update_modifies_member(): void
    {
        $member = Member::factory()->create(['first_name' => 'John']);

        $response = $this->putJson("/api/members/{$member->id}", [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['first_name' => 'Jane']);

        $this->assertDatabaseHas('members', ['id' => $member->id, 'first_name' => 'Jane']);
    }

    public function test_update_validates_email_format(): void
    {
        $member = Member::factory()->create();

        $response = $this->putJson("/api/members/{$member->id}", [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_destroy_deletes_member(): void
    {
        $member = Member::factory()->create();

        $response = $this->deleteJson("/api/members/{$member->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    public function test_bulk_destroy_deletes_multiple_members(): void
    {
        $members = Member::factory()->count(3)->create();
        $ids = $members->pluck('id')->toArray();

        $response = $this->deleteJson('/api/members/bulk', ['ids' => $ids]);

        $response->assertStatus(204);

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('members', ['id' => $id]);
        }
    }

    public function test_bulk_destroy_validates_ids(): void
    {
        $response = $this->deleteJson('/api/members/bulk', ['ids' => [99999]]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids.0']);
    }

    public function test_notify_sends_sms_to_member(): void
    {
        $member = Member::factory()->create(['mobile' => '0501234567']);

        $mockSms = Mockery::mock(SmsService::class);
        $mockSms->shouldReceive('send')
            ->once()
            ->with('0501234567', 'Test message')
            ->andReturn(true);

        $this->app->instance(SmsService::class, $mockSms);

        $response = $this->postJson("/api/members/{$member->id}/notify", [
            'message' => 'Test message',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'SMS sent successfully']);
    }

    public function test_notify_fails_when_member_has_no_mobile(): void
    {
        $member = Member::factory()->create(['mobile' => null]);

        $response = $this->postJson("/api/members/{$member->id}/notify", [
            'message' => 'Test message',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Member has no mobile number']);
    }

    public function test_notify_returns_error_when_sms_fails(): void
    {
        $member = Member::factory()->create(['mobile' => '0501234567']);

        $mockSms = Mockery::mock(SmsService::class);
        $mockSms->shouldReceive('send')
            ->once()
            ->andReturn(false);

        $this->app->instance(SmsService::class, $mockSms);

        $response = $this->postJson("/api/members/{$member->id}/notify", [
            'message' => 'Test message',
        ]);

        $response->assertStatus(500)
            ->assertJsonFragment(['error' => 'Failed to send SMS']);
    }

    public function test_notify_validates_message_required(): void
    {
        $member = Member::factory()->create(['mobile' => '0501234567']);

        $response = $this->postJson("/api/members/{$member->id}/notify", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_notify_many_sends_sms_to_multiple_members(): void
    {
        $members = Member::factory()->count(3)->create(['mobile' => '0501234567']);
        $ids = $members->pluck('id')->toArray();

        $mockSms = Mockery::mock(SmsService::class);
        $mockSms->shouldReceive('send')
            ->times(3)
            ->andReturn(true);

        $this->app->instance(SmsService::class, $mockSms);

        $response = $this->postJson('/api/members/notify', [
            'ids' => $ids,
            'message' => 'Test message',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'sent' => 3,
                'failed' => 0,
                'skipped' => 0,
            ]);
    }

    public function test_notify_many_skips_members_without_mobile(): void
    {
        $memberWithMobile = Member::factory()->create(['mobile' => '0501234567']);
        $memberWithoutMobile = Member::factory()->create(['mobile' => null]);

        $mockSms = Mockery::mock(SmsService::class);
        $mockSms->shouldReceive('send')
            ->once()
            ->andReturn(true);

        $this->app->instance(SmsService::class, $mockSms);

        $response = $this->postJson('/api/members/notify', [
            'ids' => [$memberWithMobile->id, $memberWithoutMobile->id],
            'message' => 'Test message',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'sent' => 1,
                'failed' => 0,
                'skipped' => 1,
            ]);
    }

    public function test_notify_many_counts_failures(): void
    {
        $members = Member::factory()->count(2)->create(['mobile' => '0501234567']);
        $ids = $members->pluck('id')->toArray();

        $mockSms = Mockery::mock(SmsService::class);
        $mockSms->shouldReceive('send')
            ->twice()
            ->andReturn(true, false);

        $this->app->instance(SmsService::class, $mockSms);

        $response = $this->postJson('/api/members/notify', [
            'ids' => $ids,
            'message' => 'Test message',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'sent' => 1,
                'failed' => 1,
                'skipped' => 0,
            ]);
    }

    public function test_notify_many_validates_ids_required(): void
    {
        $response = $this->postJson('/api/members/notify', [
            'message' => 'Test message',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    public function test_notify_many_validates_message_required(): void
    {
        $member = Member::factory()->create();

        $response = $this->postJson('/api/members/notify', [
            'ids' => [$member->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
