<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GenericController;
use App\Http\Controllers\MemberBankDetailsController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberCreditCardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MemberBillingSettingsController;
use App\Http\Controllers\MemberGroupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthController::class, 'login']);

// Tranzila routes (restricted to Tranzila IPs)
Route::middleware('tranzila')->group(function () {
    Route::match(['get', 'post'], 'billing/callback', [BillingController::class, 'callback']);
    Route::get('billing/success', [BillingController::class, 'success']);
    Route::get('billing/fail', [BillingController::class, 'fail']);
});

// Protected routes
Route::middleware('auth.jwt')->group(function () {
    // Auth routes
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    // Business routes
    Route::get('business', [BusinessController::class, 'show']);
    Route::put('business', [BusinessController::class, 'update']);
    Route::delete('business', [BusinessController::class, 'destroy']);
    Route::put('business/message-template', [BusinessController::class, 'updateMessageTemplate']);
    Route::post('business/message-template/reset', [BusinessController::class, 'resetMessageTemplate']);

    // Additional expense routes (must be before apiResource to avoid route conflicts)
    Route::get('expenses/stats', [ExpenseController::class, 'stats']);
    Route::post('expenses/export', [ExpenseController::class, 'export']);
    Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'downloadReceipt']);
    Route::apiResource('expenses', ExpenseController::class);

    // Additional receipt routes (must be before apiResource to avoid route conflicts)
    Route::get('receipts/stats', [ReceiptController::class, 'stats']);
    Route::post('receipts/export', [ReceiptController::class, 'export']);
    Route::get('receipts/{receipt}/pdf', [ReceiptController::class, 'downloadPdf']);
    Route::apiResource('receipts', ReceiptController::class);


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
    Route::get('packages', [GenericController::class, 'packages']);

    // Stats routes
    Route::get('search', [StatsController::class, 'search']);
    Route::get('financial-data', [StatsController::class, 'financialData']);
    Route::get('stats', [StatsController::class, 'index']);

    // Report routes
    Route::get('reports/expenses', [ReportController::class, 'expensesReport']);
    Route::get('reports/donations', [ReportController::class, 'donationsReport']);
    Route::get('reports/debts', [ReportController::class, 'debtsReport']);
    Route::get('reports/balance', [ReportController::class, 'balanceReport']);
    
    // New Reports Feature routes
    Route::get('reports/categories', [ReportController::class, 'getCategories']);
    Route::get('reports/{reportTypeId}/config', [ReportController::class, 'getReportConfig']);
    Route::post('reports/{reportTypeId}/generate', [ReportController::class, 'generateReport']);
    Route::post('reports/{reportTypeId}/export/hashavshevet', [ReportController::class, 'exportToHashavshevet']);

    // Billing routes
    Route::post('billing/member-payment', [BillingController::class, 'memberPayment']);
    Route::post('billing/business-payment', [BillingController::class, 'businessPayment']);
    Route::post('billing/charge', [BillingController::class, 'charge']);
    Route::post('billing/masav', [BillingController::class, 'masav']);

    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::put('notifications/{notification}/mark-read', [NotificationController::class, 'markRead']);
});
