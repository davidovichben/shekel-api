<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Receipt;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $statuses = ['pending', 'paid', 'cancelled', 'refunded'];
        $paymentMethods = ['credit_card', 'bank_transfer', 'cash', 'check'];
        $members = Member::all();

        $receiptNumber = 5000;

        for ($i = 0; $i < 50; $i++) {
            $totalAmount = $faker->randomFloat(2, 100, 3000);

            Receipt::create([
                'number' => 'RCP-' . $receiptNumber++,
                'member_id' => $members->isNotEmpty() ? $members->random()->id : null,
                'total' => $totalAmount,
                'status' => $faker->randomElement($statuses),
                'payment_method' => $faker->randomElement($paymentMethods),
                'date' => $faker->dateTimeBetween('-1 year', 'now'),
                'description' => $faker->optional(0.3)->sentence(),
            ]);
        }
    }
}
