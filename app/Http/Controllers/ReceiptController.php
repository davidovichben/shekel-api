<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    /**
     * Display a listing of the receipts.
     */
    public function index()
    {
        $receipts = Receipt::with('user')->latest()->paginate(15);
        return response()->json($receipts);
    }

    /**
     * Store a newly created receipt in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receipt_number' => 'required|string|unique:receipts',
            'user_id' => 'required|exists:users,id',
            'total_amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,cancelled,refunded',
            'payment_method' => 'nullable|string',
            'receipt_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $receipt = Receipt::create($validated);
        return response()->json($receipt, 201);
    }

    /**
     * Display the specified receipt.
     */
    public function show(Receipt $receipt)
    {
        $receipt->load('user');
        return response()->json($receipt);
    }

    /**
     * Update the specified receipt in storage.
     */
    public function update(Request $request, Receipt $receipt)
    {
        $validated = $request->validate([
            'receipt_number' => 'sometimes|string|unique:receipts,receipt_number,' . $receipt->id,
            'user_id' => 'sometimes|exists:users,id',
            'total_amount' => 'sometimes|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'subtotal' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pending,paid,cancelled,refunded',
            'payment_method' => 'nullable|string',
            'receipt_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $receipt->update($validated);
        return response()->json($receipt);
    }

    /**
     * Remove the specified receipt from storage.
     */
    public function destroy(Receipt $receipt)
    {
        $receipt->delete();
        return response()->json(null, 204);
    }
}
