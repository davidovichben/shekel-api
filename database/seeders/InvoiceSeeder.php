<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $paymentMethods = ['credit_card', 'bank_transfer', 'cash', 'check'];
        $members = Member::inRandomOrder()->limit(60)->get();

        $invoiceNumber = 1000;

        foreach ($members as $member) {
            $numInvoices = $faker->numberBetween(1, 3);

            for ($i = 0; $i < $numInvoices; $i++) {
                $subtotal = $faker->randomFloat(2, 100, 5000);
                $taxAmount = round($subtotal * 0.17, 2);
                $totalAmount = $subtotal + $taxAmount;
                $invoiceDate = $faker->dateTimeBetween('-1 year', 'now');

                Invoice::create([
                    'member_id' => $member->id,
                    'invoice_number' => 'INV-' . $invoiceNumber++,
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                    'subtotal' => $subtotal,
                    'payment_method' => $faker->randomElement($paymentMethods),
                    'gregorian_date' => $invoiceDate,
                    'hebrew_date' => null,
                    'paid_date' => $faker->optional(0.7)->dateTimeBetween($invoiceDate, 'now'),
                    'notes' => $faker->optional(0.3)->sentence(),
                ]);
            }
        }
    }
}
