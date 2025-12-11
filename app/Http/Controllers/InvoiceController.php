<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request)
    {
        $query = Invoice::with('member')
            ->where('business_id', current_business_id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        $invoices = $query->latest()->paginate(15);

        return response()->json($invoices);
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoices',
            'member_id' => 'required|exists:members,id',
            'total_amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,cancelled,refunded,overdue',
            'payment_method' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::create($validated);

        return response()->json($invoice, 201);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load('member');

        return response()->json($invoice);
    }
}
