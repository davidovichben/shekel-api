<?php

namespace App\Http\Controllers;

use App\Models\MemberBillingSettings;
use Illuminate\Http\Request;

class MemberBillingSettingsController extends Controller
{
    /**
     * Display the member's billing settings.
     */
    public function show(int $memberId)
    {
        $settings = MemberBillingSettings::with('creditCard')
            ->where('member_id', $memberId)
            ->first();

        return response()->json($settings);
    }

    /**
     * Store or update the member's billing settings.
     */
    public function update(Request $request, int $memberId)
    {
        $validated = $request->validate([
            'should_bill' => 'required|boolean',
            'billing_date' => 'required|in:1,10',
            'billing_type' => 'required|in:credit_card,bank,bit',
            'selected_credit_card' => 'nullable|exists:member_credit_cards,id',
        ]);

        $validated['billing_date'] = (string) $validated['billing_date'];

        $settings = MemberBillingSettings::where('member_id', $memberId)->first();

        if ($settings) {
            $settings->update($validated);
        } else {
            $validated['member_id'] = $memberId;
            $settings = MemberBillingSettings::create($validated);
        }

        $settings->load('creditCard');

        return response()->json($settings, 201);
    }
}
