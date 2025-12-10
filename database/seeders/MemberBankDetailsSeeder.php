<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Member;
use App\Models\MemberBankDetails;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class MemberBankDetailsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $banks = Bank::all();
        $members = Member::inRandomOrder()->limit(50)->get();

        foreach ($members as $member) {
            MemberBankDetails::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'bank_id' => $banks->random()->id,
                    'account_number' => $faker->numerify('########'),
                    'branch_number' => $faker->numerify('###'),
                    'id_number' => $faker->numerify('#########'),
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'billing_cap' => $faker->optional(0.3)->randomFloat(2, 500, 5000),
                ]
            );
        }
    }
}
