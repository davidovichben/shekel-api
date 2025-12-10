<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class InvoiceProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $products = Product::all();
        $invoices = Invoice::all();

        foreach ($invoices as $invoice) {
            $numProducts = $faker->numberBetween(1, 4);
            $selectedProducts = $products->random($numProducts);

            foreach ($selectedProducts as $product) {
                $amount = $faker->numberBetween(1, 5);
                $price = $product->price > 0 ? $product->price : $faker->randomFloat(2, 50, 500);
                $discount = $faker->optional(0.2)->randomFloat(2, 5, 50) ?? 0;
                $vat = 17.00;

                $totalBefore = $price * $amount - $discount;
                $vatAmount = round($totalBefore * ($vat / 100), 2);
                $totalAfter = $totalBefore + $vatAmount;

                InvoiceProduct::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'description' => $product->description,
                    'price' => $price,
                    'amount' => $amount,
                    'vat' => $vat,
                    'discount' => $discount,
                    'total_before' => $totalBefore,
                    'total_after' => $totalAfter,
                ]);
            }
        }
    }
}
