<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    /**
     * Display the current business.
     */
    public function show()
    {
        $business = Business::findOrFail(current_business_id());

        return response()->json($business);
    }

    /**
     * Update the current business.
     */
    public function update(Request $request)
    {
        $business = Business::findOrFail(current_business_id());

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
}
