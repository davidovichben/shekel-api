<?php

namespace App\Http\Controllers;

use App\Models\MemberCreditCard;
use Illuminate\Http\Request;

class MemberCreditCardController extends Controller
{
    /**
     * Display a listing of the member's credit cards.
     */
    public function index(int $memberId)
    {
        $creditCards = MemberCreditCard::where('member_id', $memberId)->get();

        return response()->json($creditCards);
    }

    /**
     * Store a newly created credit card in storage.
     */
    public function store(Request $request, int $memberId)
    {
        $validated = $request->validate([
            'last_digits' => 'required|string|size:4',
            'company' => 'required|string|in:visa,mastercard,amex,discover,jcb,diners,unknown',
            'expiration' => 'required|string|max:5',
            'full_name' => 'required|string|max:255',
        ]);

        $validated['member_id'] = $memberId;

        $creditCard = MemberCreditCard::create($validated);

        return response()->json($creditCard, 201);
    }

    /**
     * Display the specified credit card.
     */
    public function show(int $memberId, int $id)
    {
        $creditCard = MemberCreditCard::where('member_id', $memberId)
            ->findOrFail($id);

        return response()->json($creditCard);
    }

    /**
     * Update the specified credit card in storage.
     */
    public function update(Request $request, int $memberId, int $id)
    {
        $creditCard = MemberCreditCard::where('member_id', $memberId)
            ->findOrFail($id);

        $validated = $request->validate([
            'last_digits' => 'sometimes|string|size:4',
            'company' => 'sometimes|string|in:visa,mastercard,amex,discover,jcb,diners,unknown',
            'expiration' => 'sometimes|string|max:5',
            'full_name' => 'sometimes|string|max:255',
        ]);

        $creditCard->update($validated);

        return response()->json($creditCard);
    }

    /**
     * Remove the specified credit card from storage.
     */
    public function destroy(int $memberId, int $id)
    {
        $creditCard = MemberCreditCard::where('member_id', $memberId)
            ->findOrFail($id);

        $creditCard->delete();

        return response()->json(null, 204);
    }

    /**
     * Set a credit card as the default for a member.
     */
    public function setDefault(int $memberId, int $id)
    {
        $creditCard = MemberCreditCard::where('member_id', $memberId)
            ->findOrFail($id);

        MemberCreditCard::where('member_id', $memberId)
            ->update(['is_default' => 0]);

        $creditCard->update(['is_default' => 1]);

        return response()->json(['message' => 'Credit card set as default.']);
    }
}
