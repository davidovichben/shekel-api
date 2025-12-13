<?php

namespace App\Http\Controllers;

use App\Exports\BalancePdfExport;
use App\Exports\DebtsPdfExport;
use App\Exports\DynamicPdfExport;
use App\Exports\ExpensesPdfExport;
use App\Exports\HashavshevetExport;
use App\Exports\ReceiptsPdfExport;
use App\Services\ReportDefinitionsService;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MemberBillingSettings;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected $reportDefinitions;

    public function __construct(ReportDefinitionsService $reportDefinitions)
    {
        $this->reportDefinitions = $reportDefinitions;
    }
    /**
     * Generate Expense Report PDF for current month.
     */
    public function expensesReport(Request $request)
    {
        $businessId = current_business_id();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $expenses = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->with('supplier')
            ->get();

        if ($expenses->isEmpty()) {
            return response()->json([
                'message' => 'No expenses found for current month'
            ], 404);
        }

        try {
            $rows = $this->formatExpenseRowsForPdf($expenses);
            if (!$rows instanceof Collection) {
                $rows = collect($rows);
            }
            
            $rowsArray = $rows->toArray();
            $filename = 'expense_report_' . $now->format('Y-m') . '.pdf';
            
            return Excel::download(
                new ExpensesPdfExport(collect($rowsArray)), 
                $filename, 
                \Maatwebsite\Excel\Excel::MPDF
            );
        } catch (\Throwable $e) {
            Log::error('Expense Report PDF Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to generate expense report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Donations and Income Report PDF for current month.
     */
    public function donationsReport(Request $request)
    {
        $businessId = current_business_id();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $receipts = Receipt::where('business_id', $businessId)
            ->whereBetween('receipt_date', [$startOfMonth, $endOfMonth])
            ->with('user')
            ->get();

        if ($receipts->isEmpty()) {
            return response()->json([
                'message' => 'No receipts found for current month'
            ], 404);
        }

        try {
            $rows = $this->formatReceiptRowsForPdf($receipts);
            if (!$rows instanceof Collection) {
                $rows = collect($rows);
            }
            
            $rowsArray = $rows->toArray();
            $filename = 'donations_report_' . $now->format('Y-m') . '.pdf';
            
            return Excel::download(
                new ReceiptsPdfExport(collect($rowsArray)), 
                $filename, 
                \Maatwebsite\Excel\Excel::MPDF
            );
        } catch (\Throwable $e) {
            Log::error('Donations Report PDF Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to generate donations report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Community Debts Report PDF for current month.
     */
    public function debtsReport(Request $request)
    {
        $businessId = current_business_id();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $debts = Debt::where('business_id', $businessId)
            ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->with('member')
            ->get();

        if ($debts->isEmpty()) {
            return response()->json([
                'message' => 'No debts found for current month'
            ], 404);
        }

        try {
            $rows = $this->formatDebtRowsForPdf($debts);
            if (!$rows instanceof Collection) {
                $rows = collect($rows);
            }
            
            $rowsArray = $rows->toArray();
            $filename = 'debts_report_' . $now->format('Y-m') . '.pdf';
            
            return Excel::download(
                new DebtsPdfExport(collect($rowsArray)), 
                $filename, 
                \Maatwebsite\Excel\Excel::MPDF
            );
        } catch (\Throwable $e) {
            Log::error('Debts Report PDF Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to generate debts report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Financial Balance Report PDF for current month.
     */
    public function balanceReport(Request $request)
    {
        $businessId = current_business_id();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Get receipts and expenses for current month
        $receipts = Receipt::where('business_id', $businessId)
            ->whereBetween('receipt_date', [$startOfMonth, $endOfMonth])
            ->get();
        
        $expenses = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $totalIncome = (float) $receipts->sum('total');
        $totalExpenses = (float) $expenses->sum('amount');
        $balance = $totalIncome - $totalExpenses;

        try {
            $data = [
                'month' => $now->format('Y-m'),
                'totalIncome' => number_format($totalIncome, 2, '.', ''),
                'totalExpenses' => number_format($totalExpenses, 2, '.', ''),
                'balance' => number_format($balance, 2, '.', ''),
                'receipts' => $receipts,
                'expenses' => $expenses,
            ];
            
            $filename = 'balance_report_' . $now->format('Y-m') . '.pdf';
            
            return Excel::download(
                new BalancePdfExport($data), 
                $filename, 
                \Maatwebsite\Excel\Excel::MPDF
            );
        } catch (\Throwable $e) {
            Log::error('Balance Report PDF Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to generate balance report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format expenses for PDF export.
     */
    private function formatExpenseRowsForPdf($expenses)
    {
        $typeLabels = [
            'food' => 'מזון',
            'maintenance' => 'תחזוקת בית הכנסת',
            'equipment' => 'ציוד וריהוט',
            'insurance' => 'ביטוחים',
            'operations' => 'תפעול פעילויות',
            'suppliers' => 'ספקים ובעלי מקצוע',
            'management' => 'הנהלה ושכר',
        ];

        $statusLabels = [
            'paid' => 'שולם',
            'pending' => 'ממתין',
        ];

        $frequencyLabels = [
            'fixed' => 'קבוע',
            'recurring' => 'חוזר',
            'one_time' => 'פעם אחת',
        ];

        return $expenses->map(function ($expense) use ($typeLabels, $statusLabels, $frequencyLabels) {
            $date = $expense->date ? Carbon::parse($expense->date)->format('d/m/Y') : '';
            
            return [
                $frequencyLabels[$expense->frequency] ?? ($expense->frequency ?? ''),
                $statusLabels[$expense->status] ?? ($expense->status ?? ''),
                $date,
                $expense->description ?? '',
                number_format((float)$expense->amount, 2, '.', ''),
                $typeLabels[$expense->type] ?? ($expense->type ?? ''),
                $expense->supplier ? $expense->supplier->full_name : '',
            ];
        });
    }

    /**
     * Format receipts for PDF export.
     */
    private function formatReceiptRowsForPdf($receipts)
    {
        $typeLabels = [
            'vows' => 'נדרים',
            'community_donations' => 'תרומות מהקהילה',
            'external_donations' => 'תרומות חיצוניות',
            'ascensions' => 'עליות',
            'online_donations' => 'תרומות אונליין',
            'membership_fees' => 'דמי חברים',
            'other' => 'אחר',
        ];

        $statusLabels = [
            'pending' => 'ממתין',
            'paid' => 'שולם',
            'cancelled' => 'בוטל',
            'refunded' => 'הוחזר',
        ];

        $paymentMethodLabels = [
            'credit_card' => 'כרטיס אשראי',
            'cash' => 'מזומן',
            'bank_transfer' => 'העברה בנקאית',
            'check' => 'צ\'ק',
            'other' => 'אחר',
        ];

        return $receipts->map(function ($receipt) use ($typeLabels, $statusLabels, $paymentMethodLabels) {
            $date = $receipt->receipt_date ? Carbon::parse($receipt->receipt_date)->format('d/m/Y') : '';
            
            return [
                $typeLabels[$receipt->type] ?? ($receipt->type ?? ''),
                $receipt->description ?? '',
                $date,
                $paymentMethodLabels[$receipt->payment_method] ?? ($receipt->payment_method ?? ''),
                $statusLabels[$receipt->status] ?? ($receipt->status ?? ''),
                number_format((float)$receipt->total, 2, '.', ''),
                $receipt->user ? ($receipt->user->name ?? '') : '',
                $receipt->receipt_number ?? '',
            ];
        });
    }

    /**
     * Format debts for PDF export.
     */
    private function formatDebtRowsForPdf($debts)
    {
        $typeLabels = [
            'neder_shabbat' => 'נדר שבת',
            'tikun_nezek' => 'תיקון נזק',
            'dmei_chaver' => 'דמי חבר',
            'kiddush' => 'קידוש שבת',
            'neder_yom_shabbat' => 'נדר יום שבת',
            'other' => 'אחר',
        ];

        $statusLabels = [
            'pending' => 'ממתין',
            'paid' => 'שולם',
            'overdue' => 'פג תוקף',
            'cancelled' => 'בוטל',
        ];

        return $debts->map(function ($debt) use ($typeLabels, $statusLabels) {
            $lastReminder = $debt->last_reminder_sent_at 
                ? Carbon::parse($debt->last_reminder_sent_at)->format('d/m/Y') 
                : '';
            $dueDate = $debt->due_date 
                ? Carbon::parse($debt->due_date)->format('d/m/Y') 
                : '';
            
            return [
                $lastReminder,
                $statusLabels[$debt->status] ?? ($debt->status ?? ''),
                $dueDate,
                $debt->description ?? '',
                number_format((float)$debt->amount, 2, '.', ''),
                $typeLabels[$debt->type] ?? ($debt->type ?? ''),
                $debt->member ? ($debt->member->full_name ?? '') : '',
            ];
        });
    }

    /**
     * Get all report categories and their report types.
     */
    public function getCategories()
    {
        return response()->json([
            'categories' => $this->reportDefinitions->getCategories(),
        ]);
    }

    /**
     * Get configuration for a specific report type.
     */
    public function getReportConfig(string $reportTypeId)
    {
        $config = $this->reportDefinitions->getReportConfig($reportTypeId);

        if (!$config) {
            return response()->json([
                'error' => 'Report type not found',
                'message' => "Report type '{$reportTypeId}' does not exist",
            ], 404);
        }

        return response()->json($config);
    }

    /**
     * Generate a PDF report based on configuration.
     */
    public function generateReport(Request $request, string $reportTypeId)
    {
        $config = $this->reportDefinitions->getReportConfig($reportTypeId);

        if (!$config) {
            return response()->json([
                'error' => 'Report type not found',
                'message' => "Report type '{$reportTypeId}' does not exist",
            ], 404);
        }

        $validated = $request->validate([
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date',
            'sortBy' => 'required|string',
            'sortOrder' => 'required|in:asc,desc',
            'filters' => 'nullable|array',
            'resultLimit' => 'required|string',
            'columns' => 'required|array',
            'columns.*' => 'string',
        ]);

        // Validate required columns are included
        $requiredColumns = collect($config['columns'])
            ->where('required', true)
            ->pluck('id')
            ->toArray();

        $missingRequired = array_diff($requiredColumns, $validated['columns']);
        if (!empty($missingRequired)) {
            return response()->json([
                'error' => 'Missing required columns',
                'message' => 'The following required columns must be included: ' . implode(', ', $missingRequired),
                'missingColumns' => $missingRequired,
            ], 400);
        }

        try {
            $data = $this->fetchReportData($reportTypeId, $validated);
            $rows = $this->formatReportData($reportTypeId, $data, $validated['columns'], $config);
            
            // Apply result limit
            if ($validated['resultLimit'] !== 'unlimited') {
                $limit = (int) $validated['resultLimit'];
                $rows = $rows->take($limit);
            }

            $filename = $this->generateFilename($reportTypeId, $validated);
            
            return Excel::download(
                new DynamicPdfExport($rows, $validated['columns'], $config),
                $filename,
                \Maatwebsite\Excel\Excel::MPDF
            );
        } catch (\Throwable $e) {
            Log::error('Report Generation Error: ' . $e->getMessage(), [
                'report_type' => $reportTypeId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to generate report',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export report to Hashavshevet format.
     */
    public function exportToHashavshevet(Request $request, string $reportTypeId)
    {
        $config = $this->reportDefinitions->getReportConfig($reportTypeId);

        if (!$config) {
            return response()->json([
                'error' => 'Report type not found',
                'message' => "Report type '{$reportTypeId}' does not exist",
            ], 404);
        }

        $validated = $request->validate([
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date',
            'sortBy' => 'required|string',
            'sortOrder' => 'required|in:asc,desc',
            'filters' => 'nullable|array',
            'resultLimit' => 'required|string',
            'columns' => 'required|array',
            'columns.*' => 'string',
        ]);

        try {
            $data = $this->fetchReportData($reportTypeId, $validated);
            $rows = $this->formatReportData($reportTypeId, $data, $validated['columns'], $config);
            
            // Apply result limit
            if ($validated['resultLimit'] !== 'unlimited') {
                $limit = (int) $validated['resultLimit'];
                $rows = $rows->take($limit);
            }

            // Generate CSV for Hashavshevet
            $filename = $this->generateFilename($reportTypeId, $validated, 'csv');
            
            return Excel::download(
                new HashavshevetExport($rows, $validated['columns'], $config),
                $filename,
                \Maatwebsite\Excel\Excel::CSV
            );
        } catch (\Throwable $e) {
            Log::error('Hashavshevet Export Error: ' . $e->getMessage(), [
                'report_type' => $reportTypeId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to export to Hashavshevet',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch data for the report based on type and filters.
     */
    private function fetchReportData(string $reportTypeId, array $params): Collection
    {
        $businessId = current_business_id();
        $dateFrom = $params['dateFrom'] ? Carbon::parse($params['dateFrom']) : null;
        $dateTo = $params['dateTo'] ? Carbon::parse($params['dateTo']) : null;
        $filters = $params['filters'] ?? [];
        $sortBy = $params['sortBy'];
        $sortOrder = $params['sortOrder'];

        switch ($reportTypeId) {
            case 'income_monthly':
                return $this->fetchIncomeData($businessId, $dateFrom, $dateTo, $filters, $sortBy, $sortOrder);
            
            case 'expenses_monthly':
                return $this->fetchExpensesData($businessId, $dateFrom, $dateTo, $filters, $sortBy, $sortOrder);
            
            case 'expenses_high':
                return $this->fetchExpensesData($businessId, $dateFrom, $dateTo, $filters, $sortBy, $sortOrder, 1000);
            
            case 'donations_community':
                $filters['type'] = 'community_donations';
                return $this->fetchIncomeData($businessId, $dateFrom, $dateTo, $filters, $sortBy, $sortOrder);
            
            case 'donations_external':
                $filters['type'] = 'external_donations';
                return $this->fetchIncomeData($businessId, $dateFrom, $dateTo, $filters, $sortBy, $sortOrder);
            
            case 'debts_open':
            case 'debts_by_type':
            case 'debts_by_debtor':
                return $this->fetchDebtsData($businessId, $dateFrom, $dateTo, $filters, $sortBy, $sortOrder, $reportTypeId);
            
            case 'members_active':
                return $this->fetchMembersData($businessId, $filters, $sortBy, $sortOrder);
            
            case 'members_recent':
                return $this->fetchMembersRecentData($businessId, $filters, $sortBy, $sortOrder);
            
            case 'members_no_donation':
                return $this->fetchMembersNoDonationData($businessId, $filters, $sortBy, $sortOrder);
            
            case 'members_no_auto_payment':
                return $this->fetchMembersNoAutoPaymentData($businessId, $filters, $sortBy, $sortOrder);
            
            default:
                throw new \Exception("Unknown report type: {$reportTypeId}");
        }
    }

    /**
     * Fetch income/receipts data.
     */
    private function fetchIncomeData($businessId, $dateFrom, $dateTo, $filters, $sortBy, $sortOrder): Collection
    {
        $query = Receipt::where('business_id', $businessId)
            ->with('user');

        if ($dateFrom) {
            $query->where('receipt_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('receipt_date', '<=', $dateTo);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Handle sorting - map sortBy to actual column names
        if ($sortBy === 'payer_name') {
            $query->leftJoin('users', 'receipts.user_id', '=', 'users.id')
                  ->select('receipts.*')
                  ->orderBy('users.name', $sortOrder);
        } else {
            // Map sort options to actual column names
            $sortColumnMap = [
                'receipt_date' => 'receipts.receipt_date',
                'amount' => 'receipts.total',
                'type' => 'receipts.type',
                'status' => 'receipts.status',
                'payment_method' => 'receipts.payment_method',
            ];
            
            $dbColumn = $sortColumnMap[$sortBy] ?? 'receipts.' . $sortBy;
            $query->orderBy($dbColumn, $sortOrder);
        }

        return $query->get();
    }

    /**
     * Fetch expenses data.
     */
    private function fetchExpensesData($businessId, $dateFrom, $dateTo, $filters, $sortBy, $sortOrder, $minAmount = null): Collection
    {
        // Expenses don't have business_id, so we filter through suppliers
        $query = Expense::with('supplier')
            ->whereHas('supplier', function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            });

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        if ($minAmount !== null) {
            $query->where('amount', '>', $minAmount);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Handle sorting - map sortBy to actual column names
        if ($sortBy === 'supplier') {
            $query->leftJoin('members', 'expenses.supplier_id', '=', 'members.id')
                  ->select('expenses.*')
                  ->orderBy('members.first_name', $sortOrder)
                  ->orderBy('members.last_name', $sortOrder);
        } else {
            // Map sort options to actual column names
            // Note: receipt_date is mapped to date for expenses (expenses don't have receipt_date)
            $sortColumnMap = [
                'date' => 'expenses.date',
                'receipt_date' => 'expenses.date', // Map receipt_date to date for expenses
                'amount' => 'expenses.amount',
                'type' => 'expenses.type',
                'status' => 'expenses.status',
            ];
            
            $dbColumn = $sortColumnMap[$sortBy] ?? 'expenses.' . $sortBy;
            $query->orderBy($dbColumn, $sortOrder);
        }

        return $query->get();
    }

    /**
     * Fetch debts data.
     */
    private function fetchDebtsData($businessId, $dateFrom, $dateTo, $filters, $sortBy, $sortOrder, $reportTypeId): Collection
    {
        $query = Debt::where('business_id', $businessId)
            ->where('status', 'pending')
            ->with('member');

        if ($dateFrom) {
            $query->where('due_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('due_date', '<=', $dateTo);
        }

        if (isset($filters['type']) && $filters['type'] !== '') {
            $query->where('type', $filters['type']);
        }

        // Filter by member_id if provided
        if (isset($filters['member_id']) && $filters['member_id'] !== '') {
            $query->where('member_id', $filters['member_id']);
        }

        // Handle sorting - map sortBy to actual column names
        if ($sortBy === 'debtor_name') {
            $query->leftJoin('members', 'debts.member_id', '=', 'members.id')
                  ->select('debts.*')
                  ->orderBy('members.first_name', $sortOrder)
                  ->orderBy('members.last_name', $sortOrder);
        } else {
            // Map sort options to actual column names
            // Note: receipt_date and date are mapped to due_date for debts (debts don't have receipt_date or date)
            $sortColumnMap = [
                'due_date' => 'debts.due_date',
                'receipt_date' => 'debts.due_date', // Map receipt_date to due_date for debts
                'date' => 'debts.due_date', // Map date to due_date for debts
                'amount' => 'debts.amount',
                'type' => 'debts.type',
                'status' => 'debts.status',
            ];
            
            $dbColumn = $sortColumnMap[$sortBy] ?? 'debts.' . $sortBy;
            $query->orderBy($dbColumn, $sortOrder);
        }

        return $query->get();
    }

    /**
     * Fetch active members data.
     */
    private function fetchMembersData($businessId, $filters, $sortBy, $sortOrder): Collection
    {
        $query = Member::where('business_id', $businessId)
            ->whereIn('type', ['permanent', 'family_member']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if ($sortBy === 'full_name') {
            $query->orderBy('first_name', $sortOrder)
                  ->orderBy('last_name', $sortOrder);
        } else {
            // Map sort options to actual column names for members
            // Members don't have receipt_date, date, or due_date - map to created_at or ignore invalid columns
            $sortColumnMap = [
                'member_number' => 'members.member_number',
                'type' => 'members.type',
                'created_at' => 'members.created_at',
                // Map invalid date columns to created_at as fallback
                'receipt_date' => 'members.created_at',
                'date' => 'members.created_at',
                'due_date' => 'members.created_at',
            ];
            
            $dbColumn = $sortColumnMap[$sortBy] ?? 'members.' . $sortBy;
            $query->orderBy($dbColumn, $sortOrder);
        }

        return $query->get();
    }

    /**
     * Fetch members who joined in last 3 months.
     */
    private function fetchMembersRecentData($businessId, $filters, $sortBy, $sortOrder): Collection
    {
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        
        $query = Member::where('business_id', $businessId)
            ->where('created_at', '>=', $threeMonthsAgo);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if ($sortBy === 'full_name') {
            $query->orderBy('first_name', $sortOrder)
                  ->orderBy('last_name', $sortOrder);
        } else {
            // Map sort options to actual column names for members
            $sortColumnMap = [
                'member_number' => 'members.member_number',
                'type' => 'members.type',
                'created_at' => 'members.created_at',
                'receipt_date' => 'members.created_at',
                'date' => 'members.created_at',
                'due_date' => 'members.created_at',
            ];
            
            $dbColumn = $sortColumnMap[$sortBy] ?? 'members.' . $sortBy;
            $query->orderBy($dbColumn, $sortOrder);
        }

        return $query->get();
    }

    /**
     * Fetch members who didn't donate in last 3 months.
     */
    private function fetchMembersNoDonationData($businessId, $filters, $sortBy, $sortOrder): Collection
    {
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        
        $membersWithDonations = Receipt::where('business_id', $businessId)
            ->where('receipt_date', '>=', $threeMonthsAgo)
            ->distinct()
            ->pluck('user_id')
            ->filter()
            ->toArray();

        $query = Member::where('business_id', $businessId)
            ->whereNotIn('id', $membersWithDonations);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if ($sortBy === 'full_name') {
            $query->orderBy('first_name', $sortOrder)
                  ->orderBy('last_name', $sortOrder);
        } else {
            // Map sort options to actual column names for members
            $sortColumnMap = [
                'member_number' => 'members.member_number',
                'type' => 'members.type',
                'created_at' => 'members.created_at',
                'receipt_date' => 'members.created_at',
                'date' => 'members.created_at',
                'due_date' => 'members.created_at',
            ];
            
            $dbColumn = $sortColumnMap[$sortBy] ?? 'members.' . $sortBy;
            $query->orderBy($dbColumn, $sortOrder);
        }

        return $query->get();
    }

    /**
     * Fetch members without auto payment.
     */
    private function fetchMembersNoAutoPaymentData($businessId, $filters, $sortBy, $sortOrder): Collection
    {
        $membersWithAutoPayment = MemberBillingSettings::where('should_bill', true)
            ->pluck('member_id')
            ->toArray();

        $query = Member::where('business_id', $businessId)
            ->whereNotIn('id', $membersWithAutoPayment);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if ($sortBy === 'full_name') {
            $query->orderBy('first_name', $sortOrder)
                  ->orderBy('last_name', $sortOrder);
        } else {
            // Map sort options to actual column names for members
            $sortColumnMap = [
                'member_number' => 'members.member_number',
                'type' => 'members.type',
                'created_at' => 'members.created_at',
                'receipt_date' => 'members.created_at',
                'date' => 'members.created_at',
                'due_date' => 'members.created_at',
            ];
            
            $dbColumn = $sortColumnMap[$sortBy] ?? 'members.' . $sortBy;
            $query->orderBy($dbColumn, $sortOrder);
        }

        return $query->get();
    }

    /**
     * Format report data based on selected columns.
     */
    private function formatReportData(string $reportTypeId, Collection $data, array $columns, array $config): Collection
    {
        $columnLabels = collect($config['columns'])->keyBy('id')->map->label->toArray();
        
        // Get translation maps
        $typeLabels = $this->getTypeLabels($reportTypeId);
        $statusLabels = $this->getStatusLabels($reportTypeId);
        $paymentMethodLabels = $this->getPaymentMethodLabels();
        $frequencyLabels = $this->getFrequencyLabels();

        return $data->map(function ($item) use ($columns, $columnLabels, $typeLabels, $statusLabels, $paymentMethodLabels, $frequencyLabels, $reportTypeId) {
            $row = [];
            
            foreach ($columns as $columnId) {
                $value = $this->getColumnValue($item, $columnId, $typeLabels, $statusLabels, $paymentMethodLabels, $frequencyLabels, $reportTypeId);
                $row[] = $value;
            }
            
            return $row;
        });
    }

    /**
     * Get value for a specific column.
     */
    private function getColumnValue($item, string $columnId, array $typeLabels, array $statusLabels, array $paymentMethodLabels, array $frequencyLabels, string $reportTypeId)
    {
        switch ($columnId) {
            // Receipt/Income columns
            case 'receipt_number':
                return $item->receipt_number ?? '';
            case 'receipt_date':
                return $item->receipt_date ? Carbon::parse($item->receipt_date)->format('d/m/Y') : '';
            case 'hebrew_date':
                // For receipts/income, use receipt_date; for expenses, use date
                $dateField = $item->receipt_date ?? $item->date ?? null;
                return $dateField ? $this->formatHebrewDate($dateField) : '';
            case 'payer_name':
                return $item->user ? ($item->user->name ?? '') : '';
            case 'amount':
                if (isset($item->total)) {
                    return number_format((float)$item->total, 2, '.', '');
                }
                return number_format((float)($item->amount ?? 0), 2, '.', '');
            case 'type':
                $type = $item->type ?? '';
                return $typeLabels[$type] ?? $type;
            case 'payment_method':
                $method = $item->payment_method ?? '';
                return $paymentMethodLabels[$method] ?? $method;
            case 'status':
                $status = $item->status ?? '';
                return $statusLabels[$status] ?? $status;
            case 'description':
                return $item->description ?? '';
            
            // Expense columns
            case 'date':
                return $item->date ? Carbon::parse($item->date)->format('d/m/Y') : '';
            case 'supplier':
                return $item->supplier ? $item->supplier->full_name : '';
            case 'frequency':
                $freq = $item->frequency ?? '';
                return $frequencyLabels[$freq] ?? $freq;
            
            // Debt columns
            case 'debtor_name':
                return $item->member ? $item->member->full_name : '';
            case 'due_date':
                return $item->due_date ? Carbon::parse($item->due_date)->format('d/m/Y') : '';
            case 'hebrew_due_date':
                return $item->due_date ? $this->formatHebrewDate($item->due_date) : '';
            case 'last_reminder':
                return $item->last_reminder_sent_at ? Carbon::parse($item->last_reminder_sent_at)->format('d/m/Y') : '';
            
            // Member columns
            case 'member_number':
                return $item->member_number ?? '';
            case 'full_name':
                return $item->full_name ?? '';
            case 'email':
                return $item->email ?? '';
            case 'mobile':
                return $item->mobile ?? '';
            case 'phone':
                return $item->phone ?? '';
            case 'address':
                return $item->address ?? '';
            case 'city':
                return $item->city ?? '';
            
            default:
                return '';
        }
    }

    /**
     * Format date to Hebrew calendar format.
     */
    private function formatHebrewDate($date): string
    {
        if (!$date) {
            return '';
        }

        try {
            $carbon = Carbon::parse($date);
            $year = (int) $carbon->format('Y');
            $month = (int) $carbon->format('m');
            $day = (int) $carbon->format('d');

            // Convert Gregorian to Julian Day Count
            $julianDay = gregoriantojd($month, $day, $year);

            // Convert Julian Day Count to Jewish/Hebrew date
            // CAL_JEWISH_ADD_GERESHAYIM adds gershayim (״) before the last letter
            // CAL_JEWISH_ADD_ALAFIM adds alafim (אלפים) for thousands
            $hebrewDate = jdtojewish($julianDay, true, CAL_JEWISH_ADD_GERESHAYIM | CAL_JEWISH_ADD_ALAFIM);

            // Convert from ISO-8859-8 to UTF-8
            $hebrewDateUtf8 = iconv('ISO-8859-8', 'UTF-8', $hebrewDate);

            // Remove "ה אלפים" (the thousands indicator) from the date
            $hebrewDateUtf8 = str_replace('ה אלפים', '', $hebrewDateUtf8);
            $hebrewDateUtf8 = trim($hebrewDateUtf8);

            return $hebrewDateUtf8;
        } catch (\Exception $e) {
            Log::warning('Hebrew date conversion failed: ' . $e->getMessage(), [
                'date' => $date
            ]);
            // Fallback to Gregorian date if conversion fails
            return Carbon::parse($date)->format('d/m/Y');
        }
    }

    /**
     * Get type labels based on report type.
     */
    private function getTypeLabels(string $reportTypeId): array
    {
        if (strpos($reportTypeId, 'income') !== false || strpos($reportTypeId, 'donation') !== false) {
            return [
                'vows' => 'נדרים',
                'community_donations' => 'תרומות מהקהילה',
                'external_donations' => 'תרומות חיצוניות',
                'ascensions' => 'עליות',
                'online_donations' => 'תרומות אונליין',
                'membership_fees' => 'דמי חברים',
                'other' => 'אחר',
            ];
        } elseif (strpos($reportTypeId, 'expense') !== false) {
            return [
                'food' => 'מזון',
                'maintenance' => 'תחזוקת בית הכנסת',
                'equipment' => 'ציוד וריהוט',
                'insurance' => 'ביטוחים',
                'operations' => 'תפעול פעילויות',
                'suppliers' => 'ספקים ובעלי מקצוע',
                'management' => 'הנהלה ושכר',
            ];
        } elseif (strpos($reportTypeId, 'debt') !== false) {
            return [
                'neder_shabbat' => 'נדר שבת',
                'tikun_nezek' => 'תיקון נזק',
                'dmei_chaver' => 'דמי חבר',
                'kiddush' => 'קידוש שבת',
                'neder_yom_shabbat' => 'נדר יום שבת',
                'other' => 'אחר',
            ];
        } elseif (strpos($reportTypeId, 'member') !== false) {
            return [
                'permanent' => 'קבוע',
                'family_member' => 'בן משפחה',
                'guest' => 'אורח',
                'supplier' => 'ספק',
                'other' => 'אחר',
            ];
        }
        
        return [];
    }

    /**
     * Get status labels.
     */
    private function getStatusLabels(string $reportTypeId): array
    {
        if (strpos($reportTypeId, 'income') !== false || strpos($reportTypeId, 'donation') !== false) {
            return [
                'pending' => 'ממתין',
                'paid' => 'שולם',
                'cancelled' => 'בוטל',
                'refunded' => 'הוחזר',
            ];
        } elseif (strpos($reportTypeId, 'expense') !== false) {
            return [
                'paid' => 'שולם',
                'pending' => 'ממתין',
            ];
        } elseif (strpos($reportTypeId, 'debt') !== false) {
            return [
                'pending' => 'ממתין',
                'paid' => 'שולם',
                'overdue' => 'פג תוקף',
                'cancelled' => 'בוטל',
            ];
        }
        
        return [];
    }

    /**
     * Get payment method labels.
     */
    private function getPaymentMethodLabels(): array
    {
        return [
            'credit_card' => 'כרטיס אשראי',
            'cash' => 'מזומן',
            'bank_transfer' => 'העברה בנקאית',
            'check' => 'צ\'ק',
            'other' => 'אחר',
        ];
    }

    /**
     * Get frequency labels.
     */
    private function getFrequencyLabels(): array
    {
        return [
            'fixed' => 'קבוע',
            'recurring' => 'חוזר',
            'one_time' => 'פעם אחת',
        ];
    }

    /**
     * Generate filename for report.
     */
    private function generateFilename(string $reportTypeId, array $params, string $extension = 'pdf'): string
    {
        $dateStr = '';
        if ($params['dateFrom'] && $params['dateTo']) {
            $from = Carbon::parse($params['dateFrom'])->format('Y-m-d');
            $to = Carbon::parse($params['dateTo'])->format('Y-m-d');
            $dateStr = "_{$from}_to_{$to}";
        } elseif ($params['dateFrom']) {
            $from = Carbon::parse($params['dateFrom'])->format('Y-m-d');
            $dateStr = "_{$from}";
        }
        
        return "report_{$reportTypeId}{$dateStr}.{$extension}";
    }
}

