<?php

namespace App\Http\Controllers;

use App\Exports\BalancePdfExport;
use App\Exports\DebtsPdfExport;
use App\Exports\ExpensesPdfExport;
use App\Exports\ReceiptsPdfExport;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
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
}

