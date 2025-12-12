<?php

namespace App\Http\Controllers;

use App\Exports\ExpensesExport;
use App\Exports\ExpensesPdfExport;
use App\Models\Expense;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the expenses.
     */
    public function index(Request $request)
    {
        $query = $this->buildExpenseQuery($request);
        
        // Calculate total sum of all expenses (before pagination, respects filters)
        // Clone query to avoid affecting pagination query
        $totalSum = (float) (clone $query)->sum('expenses.amount');
        
        // Calculate status counts (respecting all filters except status)
        $baseQuery = $this->buildExpenseBaseQuery($request);
        $statusCounts = [
            'all' => (int) (clone $baseQuery)->count(),
            'pending' => (int) (clone $baseQuery)->where('expenses.status', 'pending')->count(),
            'paid' => (int) (clone $baseQuery)->where('expenses.status', 'paid')->count(),
        ];
        
        // Support limit and page parameters for pagination
        $perPage = $request->get('limit', 15);
        $page = $request->get('page', 1);
        $expenses = $query->paginate($perPage, ['*'], 'page', $page);

        $rows = $this->formatExpenseRows($expenses->getCollection());

        return response()->json([
            'rows' => $rows,
            'counts' => [
                'totalRows' => $expenses->total(),
                'totalPages' => $expenses->lastPage(),
                'all' => $statusCounts['all'],
                'pending' => $statusCounts['pending'],
                'paid' => $statusCounts['paid'],
            ],
            'totalSum' => number_format($totalSum, 2, '.', ''),
        ]);
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:food,maintenance,equipment,insurance,operations,suppliers,management',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'supplier_id' => 'nullable|exists:members,id',
            'status' => 'required|in:paid,pending',
            'frequency' => 'required|in:fixed,recurring,one_time',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        // Validate supplier is of type 'supplier' if provided
        if (isset($validated['supplier_id'])) {
            $supplier = Member::find($validated['supplier_id']);
            if (!$supplier || $supplier->type !== 'supplier') {
                return response()->json([
                    'message' => 'Supplier must be a member with type supplier'
                ], 422);
            }
        }

        // Handle receipt file upload
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $filename = 'expense_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('receipts', $filename, 'public');
            $validated['receipt'] = $path;
        }

        $expense = Expense::create($validated);
        $expense->load('supplier');
        $data = $this->formatExpenseDetails($expense);
        
        return response()->json($data, 201);
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense)
    {
        $expense->load('supplier');
        $data = $this->formatExpenseDetails($expense);

        return response()->json($data);
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'description' => 'nullable|string|max:500',
            'type' => 'sometimes|in:food,maintenance,equipment,insurance,operations,suppliers,management',
            'amount' => 'sometimes|numeric|min:0',
            'date' => 'sometimes|date',
            'supplier_id' => 'nullable|exists:members,id',
            'status' => 'sometimes|in:paid,pending',
            'frequency' => 'sometimes|in:fixed,recurring,one_time',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        // Validate supplier is of type 'supplier' if provided
        if (isset($validated['supplier_id'])) {
            $supplier = Member::find($validated['supplier_id']);
            if (!$supplier || $supplier->type !== 'supplier') {
                return response()->json([
                    'message' => 'Supplier must be a member with type supplier'
                ], 422);
            }
        }

        // Handle receipt file replacement
        if ($request->hasFile('receipt')) {
            // Delete old receipt file if exists
            if ($expense->receipt && Storage::disk('public')->exists($expense->receipt)) {
                Storage::disk('public')->delete($expense->receipt);
            }

            $file = $request->file('receipt');
            $filename = 'expense_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('receipts', $filename, 'public');
            $validated['receipt'] = $path;
        }

        $expense->update($validated);
        $expense->load('supplier');
        $data = $this->formatExpenseDetails($expense);
        
        return response()->json($data);
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense)
    {
        // Delete receipt file if exists
        if ($expense->receipt && Storage::disk('public')->exists($expense->receipt)) {
            Storage::disk('public')->delete($expense->receipt);
        }

        $expense->delete();
        return response()->json(null, 204);
    }

    /**
     * Export expenses to Excel/CSV/PDF.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:expenses,id',
            'status' => 'nullable|string|in:paid,pending',
            'type' => 'nullable|string|in:food,maintenance,equipment,insurance,operations,suppliers,management',
            'supplier_id' => 'nullable|integer|exists:members,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'file_type' => 'required|string|in:xls,csv,pdf',
        ]);

        // Build query based on filters
        if (!empty($validated['ids'])) {
            $query = Expense::with('supplier')->whereIn('id', $validated['ids']);
        } else {
            $query = $this->buildExpenseQuery($request);
        }

        // Apply additional filters from validation
        if (!empty($validated['status'])) {
            $query->where('expenses.status', $validated['status']);
        }

        if (!empty($validated['type'])) {
            $query->where('expenses.type', $validated['type']);
        }

        if (!empty($validated['supplier_id'])) {
            $query->where('expenses.supplier_id', $validated['supplier_id']);
        }

        if (!empty($validated['date_from'])) {
            $query->where('expenses.date', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->where('expenses.date', '<=', $validated['date_to']);
        }

        $expenses = $query->get();
        $fileType = $validated['file_type'];

        if ($fileType === 'pdf') {
            try {
                $rows = $this->formatExpenseRowsForPdf($expenses);
                // Ensure it's a Collection
                if (!$rows instanceof Collection) {
                    $rows = collect($rows);
                }
                
                // Handle empty collection
                if ($rows->isEmpty()) {
                    return response()->json([
                        'message' => 'No expenses found to export'
                    ], 404);
                }
                
                // Convert collection to array to ensure proper format
                $rowsArray = $rows->toArray();
                
                return Excel::download(new ExpensesPdfExport(collect($rowsArray)), 'expenses.pdf', \Maatwebsite\Excel\Excel::MPDF);
            } catch (\Throwable $e) {
                Log::error('PDF Export Error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                return response()->json([
                    'message' => 'Failed to generate PDF',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 500);
            }
        }

        $rows = $this->formatExpenseRowsForExport($expenses);

        $writerType = match ($fileType) {
            'xls' => \Maatwebsite\Excel\Excel::XLSX,
            'csv' => \Maatwebsite\Excel\Excel::CSV,
        };

        $extension = $fileType === 'xls' ? 'xlsx' : $fileType;

        return Excel::download(new ExpensesExport($rows), "expenses.{$extension}", $writerType);
    }

    /**
     * Download the receipt file for the specified expense.
     */
    public function downloadReceipt(Expense $expense)
    {
        if (!$expense->receipt) {
            return response()->json([
                'message' => 'Receipt file not found'
            ], 404);
        }

        if (!Storage::disk('public')->exists($expense->receipt)) {
            return response()->json([
                'message' => 'Receipt file does not exist'
            ], 404);
        }

        $filePath = Storage::disk('public')->path($expense->receipt);
        $originalFilename = basename($expense->receipt);
        
        return response()->download($filePath, $originalFilename);
    }

    /**
     * Build base query with filters (excluding status) for counting.
     */
    private function buildExpenseBaseQuery(Request $request)
    {
        $query = Expense::query();

        // Filter by supplier_id
        if ($request->has('supplier_id') && $request->supplier_id !== null && $request->supplier_id !== '') {
            $query->where('expenses.supplier_id', $request->supplier_id);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== null && $request->type !== '') {
            $query->where('expenses.type', $request->type);
        }

        // Filter by date_from (start date)
        if ($request->has('date_from') && $request->date_from !== null && $request->date_from !== '') {
            $query->where('expenses.date', '>=', $request->date_from);
        }

        // Filter by date_to (end date)
        if ($request->has('date_to') && $request->date_to !== null && $request->date_to !== '') {
            $query->where('expenses.date', '<=', $request->date_to);
        }

        return $query;
    }

    /**
     * Build query with filters and sorting.
     */
    private function buildExpenseQuery(Request $request)
    {
        $query = Expense::with('supplier');
        
        $needsSupplierJoin = false;

        // Filter by status
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('expenses.status', $request->status);
        }

        // Filter by supplier_id
        if ($request->has('supplier_id') && $request->supplier_id !== null && $request->supplier_id !== '') {
            $query->where('expenses.supplier_id', $request->supplier_id);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== null && $request->type !== '') {
            $query->where('expenses.type', $request->type);
        }

        // Filter by date_from (start date)
        if ($request->has('date_from') && $request->date_from !== null && $request->date_from !== '') {
            $query->where('expenses.date', '>=', $request->date_from);
        }

        // Filter by date_to (end date)
        if ($request->has('date_to') && $request->date_to !== null && $request->date_to !== '') {
            $query->where('expenses.date', '<=', $request->date_to);
        }

        // Handle sorting
        $sortBy = $request->get('sort_by');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Also support legacy 'sort' parameter for backward compatibility
        if (!$sortBy && $request->has('sort')) {
            $sortColumn = $request->get('sort', 'created_at');
            $sortDirection = str_starts_with($sortColumn, '-') ? 'desc' : 'asc';
            $sortColumn = ltrim($sortColumn, '-');
            
            $sortMap = [
                'amount' => 'expenses.amount',
                'date' => 'expenses.date',
                'status' => 'expenses.status',
                'type' => 'expenses.type',
            ];
            
            $dbColumn = $sortMap[$sortColumn] ?? 'expenses.created_at';
            $query->orderBy($dbColumn, $sortDirection);
        } else {
            // Validate sort_order
            if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            // Map sort_by values to database columns
            $sortMap = [
                'date' => 'expenses.date',
                'amount' => 'expenses.amount',
                'type' => 'expenses.type',
                'status' => 'expenses.status',
                'supplier' => 'members.first_name',
            ];

            $sortBy = $sortBy ?? 'created_at';
            
            // For supplier sorting, we need to join with members table
            if ($sortBy === 'supplier') {
                $needsSupplierJoin = true;
            } else {
                $dbColumn = $sortMap[$sortBy] ?? 'expenses.created_at';
                $query->orderBy($dbColumn, $sortOrder);
            }
        }

        // Apply joins if needed for supplier sorting
        if ($needsSupplierJoin) {
            $query->leftJoin('members', 'expenses.supplier_id', '=', 'members.id')
                  ->select('expenses.*')
                  ->orderBy('members.first_name', $sortOrder)
                  ->orderBy('members.last_name', $sortOrder);
        }

        return $query;
    }

    /**
     * Format expenses for datatable rows.
     */
    private function formatExpenseRows($expenses)
    {
        return $expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'description' => $expense->description,
                'type' => $expense->type,
                'amount' => $expense->amount,
                'date' => $expense->date,
                'supplierId' => $expense->supplier_id,
                'supplierName' => $expense->supplier ? $expense->supplier->full_name : null,
                'status' => $expense->status,
                'frequency' => $expense->frequency,
                'receipt' => $expense->receipt,
            ];
        });
    }

    /**
     * Format expense details for API response.
     */
    private function formatExpenseDetails(Expense $expense)
    {
        return [
            'id' => (string)$expense->id,
            'description' => $expense->description,
            'type' => $expense->type,
            'amount' => $expense->amount,
            'date' => $expense->date,
            'supplierId' => $expense->supplier_id ? (string)$expense->supplier_id : null,
            'supplierName' => $expense->supplier ? $expense->supplier->full_name : null,
            'status' => $expense->status,
            'frequency' => $expense->frequency,
            'receipt' => $expense->receipt,
            'createdAt' => $expense->created_at,
            'updatedAt' => $expense->updated_at,
        ];
    }

    /**
     * Format expenses for Excel/CSV export.
     */
    private function formatExpenseRowsForExport($expenses)
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
            'pending' => 'ממתין',
            'paid' => 'שולם',
        ];

        $frequencyLabels = [
            'fixed' => 'קבוע',
            'recurring' => 'חוזר',
            'one_time' => 'פעם אחת',
        ];

        return $expenses->map(function ($expense) use ($typeLabels, $statusLabels, $frequencyLabels) {
            return [
                $expense->supplier ? $expense->supplier->full_name : '',
                $typeLabels[$expense->type] ?? $expense->type,
                number_format((float)$expense->amount, 2, '.', ''),
                $expense->description ?? '',
                $expense->date ? $this->formatDateForResponse($expense->date) : '',
                $statusLabels[$expense->status] ?? $expense->status,
                $frequencyLabels[$expense->frequency] ?? $expense->frequency,
            ];
        });
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
            'pending' => 'ממתין',
            'paid' => 'שולם',
        ];

        $frequencyLabels = [
            'fixed' => 'קבוע',
            'recurring' => 'חוזר',
            'one_time' => 'פעם אחת',
        ];

        // Reversed order for RTL PDF layout (matching headings)
        return $expenses->map(function ($expense) use ($typeLabels, $statusLabels, $frequencyLabels) {
            $date = $expense->date ? $this->formatDateForResponse($expense->date) : '';
            
            return [
                $frequencyLabels[$expense->frequency] ?? ($expense->frequency ?? ''),
                $statusLabels[$expense->status] ?? ($expense->status ?? ''),
                $date ?? '',
                $expense->description ?? '',
                number_format((float)($expense->amount ?? 0), 2, '.', ''),
                $typeLabels[$expense->type] ?? ($expense->type ?? ''),
                $expense->supplier ? ($expense->supplier->full_name ?? '') : '',
            ];
        });
    }

    /**
     * Format date to DD/MM/YYYY for response.
     */
    private function formatDateForResponse($date)
    {
        if (!$date) {
            return '';
        }

        try {
            if ($date instanceof \Carbon\Carbon) {
                return $date->format('d/m/Y');
            }
            
            if (is_string($date)) {
                $carbon = \Carbon\Carbon::parse($date);
                return $carbon->format('d/m/Y');
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get expense statistics for dashboard.
     */
    public function stats(Request $request)
    {
        // Get month parameter or default to current month
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $monthsBack = (int) $request->get('months_back', 3);

        // Parse month to get start and end dates
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $startOfMonth = $monthDate->copy()->startOfMonth();
        $endOfMonth = $monthDate->copy()->endOfMonth();

        // Calculate monthly total
        $monthlyTotal = (float) Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Category distribution
        $categoryDistribution = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get()
            ->map(function ($item) use ($monthlyTotal) {
                $percentage = $monthlyTotal > 0 ? round(($item->total / $monthlyTotal) * 100, 0) : 0;
                return [
                    'type' => $item->type,
                    'label' => $this->getCategoryLabel($item->type),
                    'amount' => number_format((float)$item->total, 2, '.', ''),
                    'percentage' => (int)$percentage,
                ];
            })
            ->sortByDesc('amount')
            ->values();

        // Expense trend for last N months
        $trendStart = $monthDate->copy()->subMonths($monthsBack - 1)->startOfMonth();
        $trend = [];
        
        for ($i = 0; $i < $monthsBack; $i++) {
            $currentMonth = $trendStart->copy()->addMonths($i);
            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();
            
            $monthTotal = (float) Expense::whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');
            
            $trend[] = [
                'month' => $currentMonth->format('Y-m'),
                'amount' => number_format($monthTotal, 2, '.', ''),
            ];
        }

        // Unpaid expenses for the specified month
        $unpaidTotal = (float) Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'pending')
            ->sum('amount');
        
        $unpaidPercentage = $monthlyTotal > 0 ? round(($unpaidTotal / $monthlyTotal) * 100, 0) : 0;

        return response()->json([
            'monthlyTotal' => [
                'amount' => number_format($monthlyTotal, 2, '.', ''),
                'month' => $month,
                'currency' => '₪',
            ],
            'categoryDistribution' => $categoryDistribution,
            'trend' => $trend,
            'unpaidExpenses' => [
                'total' => number_format($unpaidTotal, 2, '.', ''),
                'percentage' => (int)$unpaidPercentage,
                'month' => $month,
            ],
        ]);
    }

    /**
     * Get Hebrew label for expense category.
     */
    private function getCategoryLabel(string $type): string
    {
        $labels = [
            'food' => 'מזון',
            'maintenance' => 'תחזוקת בית הכנסת',
            'equipment' => 'ציוד וריהוט',
            'insurance' => 'ביטוחים',
            'operations' => 'תפעול פעילויות',
            'suppliers' => 'ספקים ובעלי מקצוע',
            'management' => 'הנהלה ושכר',
        ];

        return $labels[$type] ?? $type;
    }
}

