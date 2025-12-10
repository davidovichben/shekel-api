<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Monthly Membership', 'description' => 'Standard monthly membership fee', 'price' => 100.00],
            ['name' => 'Annual Membership', 'description' => 'Annual membership with discount', 'price' => 1000.00],
            ['name' => 'Event Ticket', 'description' => 'General event admission', 'price' => 50.00],
            ['name' => 'Workshop Fee', 'description' => 'Workshop participation fee', 'price' => 75.00],
            ['name' => 'Donation', 'description' => 'General donation', 'price' => 0.00],
            ['name' => 'Late Payment Fee', 'description' => 'Fee for late payments', 'price' => 25.00],
            ['name' => 'Registration Fee', 'description' => 'One-time registration fee', 'price' => 150.00],
            ['name' => 'Family Membership', 'description' => 'Family membership package', 'price' => 250.00],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}
