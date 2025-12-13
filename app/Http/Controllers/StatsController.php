<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Expense;
use App\Models\Member;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    /**
     * Search across debts, members, expenses and receipts.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:255',
        ]);

        $query = $validated['q'];
        $businessId = current_business_id();

        // Search members by name
        $members = Member::where('business_id', $businessId)
            ->where(function ($q) use ($query) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
                  ->orWhere('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->full_name,
            ]);

        // Search debts by description or member name
        $debts = Debt::where('debts.business_id', $businessId)
            ->join('members', 'debts.member_id', '=', 'members.id')
            ->where(function ($q) use ($query) {
                $q->where('debts.description', 'like', "%{$query}%")
                  ->orWhereRaw("CONCAT(members.first_name, ' ', members.last_name) LIKE ?", ["%{$query}%"]);
            })
            ->select('debts.*')
            ->with('member')
            ->limit(10)
            ->get()
            ->map(fn ($debt) => [
                'id' => $debt->id,
                'name' => $debt->member ? $debt->member->full_name . ' - ' . number_format($debt->amount, 2) : $debt->description,
            ]);

        // Search receipts by receipt number or description
        $receipts = Receipt::where('business_id', $businessId)
            ->where(function ($q) use ($query) {
                $q->where('receipt_number', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($receipt) => [
                'id' => $receipt->id,
                'name' => $receipt->receipt_number,
            ]);

        return response()->json([
            'members' => $members,
            'debts' => $debts,
            'receipts' => $receipts
        ]);
    }

    /**
     * Get unified financial data (expenses, receipts, debts) with counts per category.
     * This endpoint returns all data in one request with category counts for client-side tab division.
     */
    public function financialData(Request $request)
    {
        $businessId = current_business_id();

        // Apply common filters (date range, status)
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $status = $request->get('status');

        // Get all expenses with counts per type
        $expensesQuery = Expense::query();
        if ($dateFrom) {
            $expensesQuery->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $expensesQuery->where('date', '<=', $dateTo);
        }
        if ($status) {
            $expensesQuery->where('status', $status);
        }

        $expenses = $expensesQuery->with('supplier')->get();
        $expenseCounts = Expense::select('type', DB::raw('COUNT(*) as count'))
            ->when($dateFrom, fn($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('date', '<=', $dateTo))
            ->when($status, fn($q) => $q->where('status', $status))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Get all receipts with counts per type
        $receiptsQuery = Receipt::where('business_id', $businessId);
        if ($dateFrom) {
            $receiptsQuery->where('receipt_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $receiptsQuery->where('receipt_date', '<=', $dateTo);
        }
        if ($status) {
            $receiptsQuery->where('status', $status);
        }

        $receipts = $receiptsQuery->with('user')->get();
        $receiptCounts = Receipt::where('business_id', $businessId)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->when($dateFrom, fn($q) => $q->where('receipt_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('receipt_date', '<=', $dateTo))
            ->when($status, fn($q) => $q->where('status', $status))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Get all debts with counts per type
        $debtsQuery = Debt::where('business_id', $businessId);
        if ($dateFrom) {
            $debtsQuery->where('due_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $debtsQuery->where('due_date', '<=', $dateTo);
        }
        if ($status) {
            $debtsQuery->where('status', $status);
        }

        $debts = $debtsQuery->with('member')->get();
        $debtCounts = Debt::where('business_id', $businessId)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->when($dateFrom, fn($q) => $q->where('due_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('due_date', '<=', $dateTo))
            ->when($status, fn($q) => $q->where('status', $status))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Format expenses
        $formattedExpenses = $expenses->map(function ($expense) {
            return $this->formatExpenseForUnified($expense);
        });

        // Format receipts
        $formattedReceipts = $receipts->map(function ($receipt) {
            return $this->formatReceiptForUnified($receipt);
        });

        // Format debts
        $formattedDebts = $debts->map(function ($debt) {
            return $this->formatDebtForUnified($debt);
        });

        return response()->json([
            'expenses' => [
                'data' => $formattedExpenses,
                'countsByType' => $expenseCounts,
            ],
            'receipts' => [
                'data' => $formattedReceipts,
                'countsByType' => $receiptCounts,
            ],
            'debts' => [
                'data' => $formattedDebts,
                'countsByType' => $debtCounts,
            ],
        ]);
    }

    /**
     * Format expense for unified response.
     */
    private function formatExpenseForUnified($expense)
    {
        return [
            'id' => $expense->id,
            'description' => $expense->description,
            'type' => $expense->type,
            'amount' => number_format((float)$expense->amount, 2, '.', ''),
            'date' => $expense->date,
            'supplierId' => $expense->supplier_id,
            'supplierName' => $expense->supplier ? $expense->supplier->full_name : null,
            'status' => $expense->status,
            'frequency' => $expense->frequency,
        ];
    }

    /**
     * Format receipt for unified response.
     */
    private function formatReceiptForUnified($receipt)
    {
        $receiptNumber = $receipt->receipt_number ?: null;
        $receiptDate = $receiptNumber ? $receipt->receipt_date : null;

        return [
            'id' => $receipt->id,
            'receiptNumber' => $receiptNumber,
            'userId' => $receipt->user_id,
            'userName' => $receipt->user ? $receipt->user->name : null,
            'total' => number_format((float)$receipt->total, 2, '.', ''),
            'status' => $receipt->status,
            'paymentMethod' => $receipt->payment_method,
            'receiptDate' => $receiptDate,
            'description' => $receipt->description,
            'type' => $receipt->type,
        ];
    }

    /**
     * Format debt for unified response.
     */
    private function formatDebtForUnified($debt)
    {
        return [
            'id' => $debt->id,
            'memberId' => $debt->member_id,
            'memberName' => $debt->member ? $debt->member->full_name : null,
            'type' => $debt->type,
            'amount' => number_format((float)$debt->amount, 2, '.', ''),
            'description' => $debt->description,
            'dueDate' => $debt->due_date,
            'status' => $debt->status,
        ];
    }

    /**
     * Get general dashboard statistics.
     */
    public function index(Request $request)
    {
        $businessId = current_business_id();

        // Get month parameter or default to current month
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $startOfMonth = $monthDate->copy()->startOfMonth();
        $endOfMonth = $monthDate->copy()->endOfMonth();

        // Previous month for comparison
        $prevMonthDate = $monthDate->copy()->subMonth();
        $prevStartOfMonth = $prevMonthDate->copy()->startOfMonth();
        $prevEndOfMonth = $prevMonthDate->copy()->endOfMonth();

        // Current month totals
        $currentMonthDonations = (float) Receipt::where('business_id', $businessId)
            ->whereBetween('receipt_date', [$startOfMonth, $endOfMonth])
            ->sum('total');

        $currentMonthExpenses = (float) Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Previous month totals for percentage calculation
        $prevMonthDonations = (float) Receipt::where('business_id', $businessId)
            ->whereBetween('receipt_date', [$prevStartOfMonth, $prevEndOfMonth])
            ->sum('total');

        $prevMonthExpenses = (float) Expense::whereBetween('date', [$prevStartOfMonth, $prevEndOfMonth])
            ->sum('amount');

        // Calculate percentage changes
        $donationsChangePercent = $prevMonthDonations > 0
            ? round((($currentMonthDonations - $prevMonthDonations) / $prevMonthDonations) * 100, 0)
            : 0;

        $expensesChangePercent = $prevMonthExpenses > 0
            ? round((($currentMonthExpenses - $prevMonthExpenses) / $prevMonthExpenses) * 100, 0)
            : 0;

        // Open debts (status = pending)
        $openDebts = Debt::where('business_id', $businessId)
            ->where('status', 'pending')
            ->get();

        $openDebtsTotal = (float) $openDebts->sum('amount');
        $openDebtsCount = $openDebts->count();

        // Monthly balance (donations - expenses)
        $monthlyBalance = $currentMonthDonations - $currentMonthExpenses;

        // Last month balance data
        $lastMonthIncome = (float) Receipt::where('business_id', $businessId)
            ->whereBetween('receipt_date', [$prevStartOfMonth, $prevEndOfMonth])
            ->sum('total');

        $lastMonthExpenses = (float) Expense::whereBetween('date', [$prevStartOfMonth, $prevEndOfMonth])
            ->sum('amount');

        $lastMonthBalance = $lastMonthIncome - $lastMonthExpenses;

        // Debt distribution by type (all debts, not just current month)
        $debtDistributionData = Debt::where('business_id', $businessId)
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get();

        $debtDistributionTotal = (float) $debtDistributionData->sum('total');

        $debtDistribution = $debtDistributionData->map(function ($item) use ($debtDistributionTotal) {
            $amount = (float) $item->total;
            $percentage = $debtDistributionTotal > 0
                ? round(($amount / $debtDistributionTotal) * 100, 0)
                : 0;
            return [
                'type' => $item->type,
                'label' => $this->getDebtTypeLabel($item->type),
                'amount' => number_format($amount, 2, '.', ''),
                'percentage' => (int)$percentage,
            ];
        })->sortByDesc(function ($item) {
            return (float) $item['amount'];
        })->values();

        // Semi-annual trend (6 months: current + 5 previous)
        $trendStart = $monthDate->copy()->subMonths(5)->startOfMonth();
        $semiAnnualTrend = [];

        for ($i = 0; $i < 6; $i++) {
            $currentMonth = $trendStart->copy()->addMonths($i);
            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();

            $monthIncome = (float) Receipt::where('business_id', $businessId)
                ->whereBetween('receipt_date', [$monthStart, $monthEnd])
                ->sum('total');

            $monthExpenses = (float) Expense::whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');

            $semiAnnualTrend[] = [
                'month' => $currentMonth->format('Y-m'),
                'income' => number_format($monthIncome, 2, '.', ''),
                'expenses' => number_format($monthExpenses, 2, '.', ''),
            ];
        }

        return response()->json([
            'summaryCards' => [
                'donations' => [
                    'amount' => number_format($currentMonthDonations, 2, '.', ''),
                    'changePercent' => (int)$donationsChangePercent,
                    'changeLabel' => 'מהחודש שעבר',
                ],
                'expenses' => [
                    'amount' => number_format($currentMonthExpenses, 2, '.', ''),
                    'changePercent' => (int)$expensesChangePercent,
                    'changeLabel' => 'מהחודש שעבר',
                ],
                'openDebts' => [
                    'amount' => number_format($openDebtsTotal, 2, '.', ''),
                    'count' => $openDebtsCount,
                    'label' => 'חובות פעילים',
                ],
                'monthlyBalance' => [
                    'amount' => number_format($monthlyBalance, 2, '.', ''),
                    'label' => 'הכנסות מחות הוצאות',
                ],
            ],
            'lastMonthBalance' => [
                'income' => number_format($lastMonthIncome, 2, '.', ''),
                'expenses' => number_format($lastMonthExpenses, 2, '.', ''),
                'balance' => number_format($lastMonthBalance, 2, '.', ''),
                'label' => 'ביתרה',
            ],
            'debtDistribution' => [
                'total' => number_format($debtDistributionTotal, 2, '.', ''),
                'categories' => $debtDistribution,
            ],
            'semiAnnualTrend' => [
                'months' => $semiAnnualTrend,
            ],
        ]);
    }

    /**
     * Get Hebrew label for debt type.
     */
    private function getDebtTypeLabel(string $type): string
    {
        $labels = [
            'neder_shabbat' => 'נדר שבת',
            'tikun_nezek' => 'תיקון נזק',
            'dmei_chaver' => 'דמי חבר',
            'kiddush' => 'קידוש שבת',
            'neder_yom_shabbat' => 'נדר יום שבת',
            'other' => 'אחר',
        ];

        return $labels[$type] ?? $type;
    }
}
