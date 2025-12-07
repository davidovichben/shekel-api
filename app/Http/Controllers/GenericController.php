<?php

namespace App\Http\Controllers;

use App\Models\Bank;

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
}
