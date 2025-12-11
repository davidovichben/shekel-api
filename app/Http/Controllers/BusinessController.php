<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    /**
     * Display a listing of businesses.
     */
    public function index()
    {
        $businesses = Business::paginate(15);

        return response()->json([
            'rows' => $businesses->items(),
            'counts' => [
                'total_rows' => $businesses->total(),
                'total_pages' => $businesses->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created business in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_number' => 'required|string|max:15',
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'type' => 'required|in:npo,exempt,licensed,company',
            'website' => 'nullable|string',
            'preferred_date_format' => 'sometimes|in:gregorian,hebrew',
            'show_details_on_invoice' => 'boolean',
            'synagogue_name' => 'required|string|max:255',
            'synagogue_phone' => 'nullable|string|max:15',
            'synagogue_address' => 'nullable|string',
            'synagogue_email' => 'nullable|email|max:255',
        ]);

        $business = Business::create($validated);

        return response()->json($business, 201);
    }

    /**
     * Display the specified business.
     */
    public function show(Business $business)
    {
        return response()->json($business);
    }

    /**
     * Update the specified business in storage.
     */
    public function update(Request $request, Business $business)
    {
        $validated = $request->validate([
            'business_number' => 'sometimes|string|max:15',
            'name' => 'sometimes|string|max:255',
            'logo' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'type' => 'sometimes|in:npo,exempt,licensed,company',
            'website' => 'nullable|string',
            'preferred_date_format' => 'sometimes|in:gregorian,hebrew',
            'show_details_on_invoice' => 'boolean',
            'synagogue_name' => 'sometimes|string|max:255',
            'synagogue_phone' => 'nullable|string|max:15',
            'synagogue_address' => 'nullable|string',
            'synagogue_email' => 'nullable|email|max:255',
        ]);

        $business->update($validated);

        return response()->json($business);
    }

    /**
     * Remove the specified business from storage.
     */
//    public function destroy(Business $business)
//    {
//        $business->delete();
//
//        return response()->json(null, 204);
//    }
}
