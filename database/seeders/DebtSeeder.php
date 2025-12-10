<?php

namespace Database\Seeders;

use App\Models\Debt;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DebtSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $statuses = ['pending', 'paid', 'overdue', 'cancelled'];
        $members = Member::inRandomOrder()->limit(80)->get();

        foreach ($members as $member) {
            $numDebts = $faker->numberBetween(1, 3);

            for ($i = 0; $i < $numDebts; $i++) {
                Debt::create([
                    'member_id' => $member->id,
                    'amount' => $faker->randomFloat(2, 50, 2000),
                    'description' => $faker->randomElement([
                        'Monthly membership fee',
                        'Event registration',
                        'Annual dues',
                        'Workshop fee',
                        'Late payment penalty',
                    ]),
                    'due_date' => $faker->dateTimeBetween('-6 months', '+3 months'),
                    'status' => $faker->randomElement($statuses),
                ]);
            }
        }
    }
}
