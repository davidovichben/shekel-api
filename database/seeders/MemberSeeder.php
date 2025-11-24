<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Distribution of 300 members across types
        $typeDistribution = [
            'permanent' => 100,
            'family_member' => 80,
            'guest' => 50,
            'supplier' => 30,
            'other' => 25,
            'primary_admin' => 10,
            'secondary_admin' => 5,
        ];

        $genders = ['male', 'female', 'other'];
        $contactTypes = ['child', 'parent', 'sibling', 'spouse', 'brother-in-law', 'grandparent', 'son-in-law', 'guest', 'phone_operator', 'other'];

        foreach ($typeDistribution as $type => $count) {
            for ($i = 0; $i < $count; $i++) {
                Member::create([
                    'first_name' => $faker->firstName,
                    'last_name' => $faker->lastName,
                    'mobile' => $faker->phoneNumber,
                    'phone' => $faker->optional(0.7)->phoneNumber,
                    'email' => $faker->optional(0.8)->email,
                    'gender' => $faker->randomElement($genders),
                    'address' => $faker->streetAddress,
                    'address_2' => $faker->optional(0.3)->secondaryAddress,
                    'city' => $faker->city,
                    'country' => $faker->country,
                    'zipcode' => $faker->postcode,
                    'gregorian_birth_date' => $faker->optional(0.8)->dateTimeBetween('-80 years', '-18 years'),
                    'hebrew_birth_date' => $faker->optional(0.5)->date('Y-m-d'),
                    'gregorian_wedding_date' => $faker->optional(0.4)->dateTimeBetween('-50 years', 'now'),
                    'hebrew_wedding_date' => $faker->optional(0.3)->date('Y-m-d'),
                    'gregorian_death_date' => $faker->optional(0.05)->dateTimeBetween('-10 years', 'now'),
                    'hebrew_death_date' => $faker->optional(0.03)->date('Y-m-d'),
                    'contact_person' => $faker->optional(0.4)->name,
                    'contact_person_type' => $faker->optional(0.4)->randomElement($contactTypes),
                    'tag' => $faker->optional(0.3)->word,
                    'title' => $faker->optional(0.2)->title,
                    'type' => $type,
                    'member_number' => $faker->unique()->numerify('MEM-#####'),
                    'has_website_account' => $faker->boolean(30),
                    'should_mail' => $faker->boolean(60),
                ]);
            }
        }
    }
}