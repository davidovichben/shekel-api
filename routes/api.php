<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('logout-all', [AuthController::class, 'logoutAll']);
Route::get('user', [AuthController::class, 'user']);

// Resource routes
Route::apiResource('members', MemberController::class);
Route::apiResource('receipts', ReceiptController::class);

// Additional member routes
Route::get('members/type/{type}', [MemberController::class, 'byType']);
Route::get('members/export', [MemberController::class, 'export']);
Route::get('members-with-accounts', [MemberController::class, 'withWebsiteAccounts']);
Route::get('members-mail-list', [MemberController::class, 'mailList']);
