<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemberGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(GroupSeeder::class);

        $group = Group::first();

        DB::table('member_groups')->updateOrInsert(
            ['member_id' => 51, 'group_id' => $group->id],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }
}
