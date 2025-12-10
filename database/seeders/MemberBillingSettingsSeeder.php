<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberBillingSettings;
use App\Models\MemberCreditCard;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class MemberBillingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $billingTypes = ['credit_card', 'bank_transfer', 'cash', 'check'];
        $members = Member::inRandomOrder()->limit(100)->get();

        foreach ($members as $member) {
            $billingType = $faker->randomElement($billingTypes);
            $creditCard = null;

            if ($billingType === 'credit_card') {
                $creditCard = MemberCreditCard::where('member_id', $member->id)->first();
            }

            MemberBillingSettings::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'should_bill' => $faker->boolean(70),
                    'billing_date' => $faker->numberBetween(1, 28),
                    'billing_type' => $billingType,
                    'selected_credit_card' => $creditCard?->id,
                ]
            );
        }
    }
}
