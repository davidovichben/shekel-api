<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemberCreditCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('member_credit_cards')->insert([
            [
                'member_id' => 51,
                'token' => '1234567890123456789',
                'last_digits' => '4532',
                'company' => 'Visa',
                'expiration' => '12/27',
                'full_name' => 'John Doe',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'member_id' => 51,
                'token' => '9876543210987654321',
                'last_digits' => '8721',
                'company' => 'Mastercard',
                'expiration' => '03/26',
                'full_name' => 'John Doe',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'member_id' => 51,
                'token' => '5678901234567890123',
                'last_digits' => '1234',
                'company' => 'Amex',
                'expiration' => '09/28',
                'full_name' => 'John Doe',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'member_id' => 51,
                'token' => '3456789012345678901',
                'last_digits' => '5678',
                'company' => 'Visa',
                'expiration' => '06/25',
                'full_name' => 'John Doe',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'member_id' => 51,
                'token' => '7890123456789012345',
                'last_digits' => '9012',
                'company' => 'Mastercard',
                'expiration' => '11/26',
                'full_name' => 'John Doe',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
