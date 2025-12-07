<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            ['code' => '4', 'name' => 'בנק יהב לעובדי המדינה'],
            ['code' => '9', 'name' => 'בנק הדואר'],
            ['code' => '10', 'name' => 'בנק לאומי לישראל'],
            ['code' => '11', 'name' => 'בנק דיסקונט לישראל'],
            ['code' => '12', 'name' => 'בנק הפועלים'],
            ['code' => '13', 'name' => 'בנק אגוד לישראל'],
            ['code' => '14', 'name' => 'בנק אוצר החייל'],
            ['code' => '17', 'name' => 'בנק מרכנתיל דיסקונט'],
            ['code' => '18', 'name' => 'בנק וואן זירו'],
            ['code' => '20', 'name' => 'בנק מזרחי טפחות'],
            ['code' => '26', 'name' => 'יובנק'],
            ['code' => '31', 'name' => 'הבנק הבינלאומי הראשון לישראל'],
            ['code' => '34', 'name' => 'בנק ערבי ישראלי'],
            ['code' => '46', 'name' => 'בנק מסד'],
            ['code' => '52', 'name' => 'בנק פועלי אגודת ישראל'],
            ['code' => '54', 'name' => 'בנק ירושלים'],
            ['code' => '68', 'name' => 'בנק דקסיה ישראל'],
            ['code' => '99', 'name' => 'בנק ישראל'],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(
                ['code' => $bank['code']],
                ['name' => $bank['name']]
            );
        }
    }
}
