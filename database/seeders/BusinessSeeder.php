<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        Business::updateOrCreate(
            ['business_number' => '123456789'],
            [
                'name' => 'בית הכנסת המרכזי',
                'logo' => null,
                'phone' => '03-1234567',
                'address' => 'רחוב הרצל 1, תל אביב',
                'email' => 'info@example.com',
                'type' => 'npo',
                'website' => 'https://example.com',
                'preferred_date_format' => 'hebrew',
                'show_details_on_invoice' => true,
                'synagogue_name' => 'בית הכנסת המרכזי',
                'synagogue_phone' => '03-1234567',
                'synagogue_address' => 'רחוב הרצל 1, תל אביב',
                'synagogue_email' => 'synagogue@example.com',
                'message_template' => 'שלום, זוהי הודעת תזכורת לתשלום חוב על סך [סכום החוב] מבית הכנסת ״אהל יצחק״, נבקשך להסדיר את התשלום בהקדם. בתודה מראש גבאי בית הכנסת',
            ]
        );
    }
}
