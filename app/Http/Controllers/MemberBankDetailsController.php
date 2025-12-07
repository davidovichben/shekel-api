<?php

namespace App\Http\Controllers;

use App\Models\MemberBankDetails;
use Illuminate\Http\Request;

class MemberBankDetailsController extends Controller
{
    /**
     * Display a listing of the member's bank details.
     */
    public function index(int $memberId)
    {
        $bankDetails = MemberBankDetails::with('bank')
            ->where('member_id', $memberId)
            ->get();

        return response()->json($bankDetails);
    }

    /**
     * Store a newly created bank details in storage.
     */
    public function store(Request $request, int $memberId)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'account_number' => 'required|string|max:255',
            'branch_number' => 'required|string|max:255',
            'id_number' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'billing_cap' => 'nullable|numeric|min:0',
        ]);

        $validated['member_id'] = $memberId;

        $bankDetails = MemberBankDetails::create($validated);
        $bankDetails->load('bank');

        return response()->json($bankDetails, 201);
    }

    /**
     * Display the specified bank details.
     */
    public function show(int $memberId, int $id)
    {
        $bankDetails = MemberBankDetails::with('bank')
            ->where('member_id', $memberId)
            ->findOrFail($id);

        return response()->json($bankDetails);
    }

    /**
     * Update the specified bank details in storage.
     */
    public function update(Request $request, int $memberId, int $id)
    {
        $bankDetails = MemberBankDetails::where('member_id', $memberId)
            ->findOrFail($id);

        $validated = $request->validate([
            'bank_id' => 'sometimes|exists:banks,id',
            'account_number' => 'sometimes|string|max:255',
            'branch_number' => 'sometimes|string|max:255',
            'id_number' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'billing_cap' => 'nullable|numeric|min:0',
        ]);

        $bankDetails->update($validated);
        $bankDetails->load('bank');

        return response()->json($bankDetails);
    }

    /**
     * Remove the specified bank details from storage.
     */
    public function destroy(int $memberId, int $id)
    {
        $bankDetails = MemberBankDetails::where('member_id', $memberId)
            ->findOrFail($id);

        $bankDetails->delete();

        return response()->json(null, 204);
    }
}
