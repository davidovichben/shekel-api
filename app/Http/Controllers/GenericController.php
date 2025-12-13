<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Package;

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
     * Display a listing of all packages.
     */
    public function packages()
    {
        $packages = Package::all(['id', 'name', 'price', 'features', 'paid_features']);

        return response()->json($packages);
    }
}
