<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Debt;
use App\Models\Member;
use App\Models\Receipt;
use Illuminate\Http\Request;

class GenericController extends Controller
{
    /**
     * Display a listing of all banks.
     */
    public function banks()
    {
        $banks = Bank::orderBy('code')->get(['id', 'name']);

        return response()->json($banks);
    }

    /**
     * Search across debts, members, expenses and receipts.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:255',
        ]);

        $query = $validated['q'];
        $businessId = current_business_id();

        // Search members by name
        $members = Member::where('business_id', $businessId)
            ->where(function ($q) use ($query) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
                  ->orWhere('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->full_name,
            ]);

        // Search debts by description or member name
        $debts = Debt::where('debts.business_id', $businessId)
            ->join('members', 'debts.member_id', '=', 'members.id')
            ->where(function ($q) use ($query) {
                $q->where('debts.description', 'like', "%{$query}%")
                  ->orWhereRaw("CONCAT(members.first_name, ' ', members.last_name) LIKE ?", ["%{$query}%"]);
            })
            ->select('debts.*')
            ->with('member')
            ->limit(10)
            ->get()
            ->map(fn ($debt) => [
                'id' => $debt->id,
                'name' => $debt->member ? $debt->member->full_name . ' - ' . number_format($debt->amount, 2) : $debt->description,
            ]);

        // Search receipts by receipt number or notes
        $receipts = Receipt::where('business_id', $businessId)
            ->where(function ($q) use ($query) {
                $q->where('receipt_number', 'like', "%{$query}%")
                  ->orWhere('notes', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($receipt) => [
                'id' => $receipt->id,
                'name' => $receipt->receipt_number,
            ]);

        return response()->json([
            'members' => $members,
            'debts' => $debts,
            'receipts' => $receipts
        ]);
    }
}
