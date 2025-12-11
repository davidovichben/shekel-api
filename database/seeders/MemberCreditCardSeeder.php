<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberCreditCard;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class MemberCreditCardSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companies = ['Visa', 'Mastercard', 'Amex', 'Diners'];
        $members = Member::inRandomOrder()->limit(40)->get();

        foreach ($members as $member) {
            $numCards = $faker->numberBetween(1, 3);
            $hasDefault = false;

            for ($i = 0; $i < $numCards; $i++) {
                $isDefault = !$hasDefault && $faker->boolean(70);
                if ($isDefault) {
                    $hasDefault = true;
                }

                MemberCreditCard::create([
                    'member_id' => $member->id,
                    'token' => $faker->numerify('###################'),
                    'last_digits' => $faker->numerify('####'),
                    'company' => $faker->randomElement($companies),
                    'expiration' => $faker->numerify('##') . '/' . $faker->numberBetween(25, 30),
                    'full_name' => $member->first_name . ' ' . $member->last_name,
                    'is_default' => $isDefault,
                ]);
            }
        }
    }
}
