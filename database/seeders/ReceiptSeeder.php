<?php

namespace Database\Seeders;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $statuses = ['pending', 'completed', 'cancelled'];
        $paymentMethods = ['credit_card', 'bank_transfer', 'cash', 'check'];
        $users = User::all();

        $receiptNumber = 5000;

        for ($i = 0; $i < 50; $i++) {
            $subtotal = $faker->randomFloat(2, 100, 3000);
            $taxAmount = round($subtotal * 0.17, 2);
            $totalAmount = $subtotal + $taxAmount;

            Receipt::create([
                'receipt_number' => 'RCP-' . $receiptNumber++,
                'user_id' => $users->random()->id,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'subtotal' => $subtotal,
                'status' => $faker->randomElement($statuses),
                'payment_method' => $faker->randomElement($paymentMethods),
                'receipt_date' => $faker->dateTimeBetween('-1 year', 'now'),
                'notes' => $faker->optional(0.3)->sentence(),
            ]);
        }
    }
}
