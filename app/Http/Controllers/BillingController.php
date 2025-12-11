<?php

namespace App\Http\Controllers;

use App\Models\MemberCreditCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    /**
     * Store a credit card token (mock implementation).
     * In production, this will call Tranzila's store token API.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'last_digits' => 'required|string|size:4',
            'company' => 'required|string|in:visa,mastercard,amex,discover,jcb,diners,unknown',
            'expiration' => 'required|string|max:5',
            'full_name' => 'required|string|max:255',
        ]);

        // Mock: Generate a random token (in production, this comes from Tranzila)
        $token = 'TRZ_' . Str::random(32);

        // Store the credit card
        $creditCard = MemberCreditCard::create([
            'member_id' => $validated['member_id'],
            'token' => $token,
            'last_digits' => $validated['last_digits'],
            'company' => $validated['company'],
            'expiration' => $validated['expiration'],
            'full_name' => $validated['full_name'],
            'is_default' => MemberCreditCard::where('member_id', $validated['member_id'])->count() === 0,
        ]);

        return response()->json([
            'success' => true,
            'credit_card' => [
                'id' => $creditCard->id,
                'last_digits' => $creditCard->last_digits,
                'company' => $creditCard->company,
                'expiration' => $creditCard->expiration,
                'full_name' => $creditCard->full_name,
                'is_default' => $creditCard->is_default,
            ],
        ], 201);
    }

    /**
     * Charge a credit card (mock implementation).
     * In production, this will call Tranzila's charge API.
     */
    public function charge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'credit_card_id' => 'required|exists:member_credit_cards,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        $creditCard = MemberCreditCard::findOrFail($validated['credit_card_id']);

        // Mock: Simulate processing delay
        usleep(500000); // 0.5 second delay

        // Mock: Always return success (in production, this depends on Tranzila response)
        $transactionId = 'TXN_' . Str::random(16);

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transactionId,
                'amount' => number_format((float) $validated['amount'], 2, '.', ''),
                'credit_card_id' => $creditCard->id,
                'last_digits' => $creditCard->last_digits,
                'description' => $validated['description'] ?? null,
                'status' => 'completed',
            ],
        ]);
    }

}
