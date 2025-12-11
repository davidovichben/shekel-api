<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\GenericController;
use App\Http\Controllers\MemberBankDetailsController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberCreditCardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MemberBillingSettingsController;
use App\Http\Controllers\MemberGroupController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth.jwt')->group(function () {
    // Auth routes
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    // Business routes
    Route::get('business', [BusinessController::class, 'show']);
    Route::put('business', [BusinessController::class, 'update']);

    // Member routes
    Route::delete('members/bulk', [MemberController::class, 'bulkDestroy']);
    Route::get('members/list', [MemberController::class, 'list']);
    Route::apiResource('members', MemberController::class);
    Route::get('members/type/{type}', [MemberController::class, 'byType']);
    Route::post('members/export', [MemberController::class, 'export']);
    Route::post('members/{member}/notify', [MemberController::class, 'notify']);
    Route::post('members/notify', [MemberController::class, 'notifyMany']);
    Route::get('members-with-accounts', [MemberController::class, 'withWebsiteAccounts']);
    Route::get('members-mail-list', [MemberController::class, 'mailList']);

    // Debt routes
    Route::apiResource('debts', DebtController::class);
    Route::get('members/{memberId}/debts/{status}', [DebtController::class, 'byMember'])->where('status', 'open|closed');
    Route::post('debts/bulk', [DebtController::class, 'bulkStore']);
    Route::post('debts/export', [DebtController::class, 'export']);
    Route::post('debts/{debt}/reminder', [DebtController::class, 'sendReminder']);

    // Member group routes
    Route::get('members/{memberId}/groups', [MemberGroupController::class, 'index']);
    Route::post('members/{memberId}/groups', [MemberGroupController::class, 'store']);
    Route::delete('members/{memberId}/groups/{groupId}', [MemberGroupController::class, 'destroy']);

    // Member bank details routes
    Route::get('members/{memberId}/bank-details', [MemberBankDetailsController::class, 'index']);
    Route::post('members/{memberId}/bank-details', [MemberBankDetailsController::class, 'store']);
    Route::get('members/{memberId}/bank-details/{id}', [MemberBankDetailsController::class, 'show']);
    Route::put('members/{memberId}/bank-details/{id}', [MemberBankDetailsController::class, 'update']);
    Route::delete('members/{memberId}/bank-details/{id}', [MemberBankDetailsController::class, 'destroy']);

    // Member credit card routes
    Route::get('members/{memberId}/credit-cards', [MemberCreditCardController::class, 'index']);
    Route::post('members/{memberId}/credit-cards', [MemberCreditCardController::class, 'store']);
    Route::get('members/{memberId}/credit-cards/{id}', [MemberCreditCardController::class, 'show']);
    Route::put('members/{memberId}/credit-cards/{id}', [MemberCreditCardController::class, 'update']);
    Route::delete('members/{memberId}/credit-cards/{id}', [MemberCreditCardController::class, 'destroy']);
    Route::put('members/{memberId}/credit-cards/{id}/set-default', [MemberCreditCardController::class, 'setDefault']);

    // Group routes
    Route::get('groups/list', [GroupController::class, 'list']);
    Route::get('members/{memberId}/available-groups', [GroupController::class, 'index']);
    Route::post('groups', [GroupController::class, 'store']);

    // Member billing settings routes
    Route::get('members/{memberId}/billing-settings', [MemberBillingSettingsController::class, 'show']);
    Route::put('members/{memberId}/billing-settings', [MemberBillingSettingsController::class, 'update']);

    // Generic routes
    Route::get('banks', [GenericController::class, 'banks']);

    // Billing routes
    Route::post('billing/store', [BillingController::class, 'store']);
    Route::post('billing/charge', [BillingController::class, 'charge']);
});
