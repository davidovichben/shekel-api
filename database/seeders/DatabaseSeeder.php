<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed banks first (no dependencies)
        $this->call(BankSeeder::class);

        // Seed members
        $this->call(MemberSeeder::class);

        // Seed groups (depends on members)
        $this->call(GroupSeeder::class);
    }
}
