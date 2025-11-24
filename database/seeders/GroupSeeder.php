<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Member;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Board Members', 'description' => 'Board of directors'],
            ['name' => 'Volunteers', 'description' => 'Active volunteers'],
            ['name' => 'Donors', 'description' => 'Financial contributors'],
            ['name' => 'Newsletter', 'description' => 'Newsletter subscribers'],
            ['name' => 'Events Committee', 'description' => 'Event planning team'],
            ['name' => 'Youth Group', 'description' => 'Youth members'],
            ['name' => 'Seniors', 'description' => 'Senior members'],
            ['name' => 'New Members', 'description' => 'Recently joined'],
        ];

        foreach ($groups as $group) {
            Group::create($group);
        }

        // Assign random members to groups
        $allGroups = Group::all();
        $members = Member::all();

        foreach ($members as $member) {
            // Each member belongs to 0-3 random groups
            $randomGroups = $allGroups->random(rand(0, 3))->pluck('id');
            $member->groups()->attach($randomGroups);
        }
    }
}