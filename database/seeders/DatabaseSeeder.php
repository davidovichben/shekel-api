<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Base tables (no dependencies)
            UserSeeder::class,
            BankSeeder::class,
            ProductSeeder::class,

            // Members (depends on nothing)
            MemberSeeder::class,

            // Groups and member-group relationships
            GroupSeeder::class,

            // Member-related tables (depends on members, banks)
            MemberCreditCardSeeder::class,
            MemberBankDetailsSeeder::class,
            MemberBillingSettingsSeeder::class,

            // Financial records (depends on members)
            DebtSeeder::class,
            InvoiceSeeder::class,

            // Invoice products (depends on invoices and products)
            InvoiceProductSeeder::class,

            // Receipts (depends on users)
            ReceiptSeeder::class,
        ]);
    }
}
