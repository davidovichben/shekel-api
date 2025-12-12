<?php

namespace App\Http\Controllers;

use App\Exports\ReceiptsExport;
use App\Exports\ReceiptsPdfExport;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ReceiptController extends Controller
{
    /**
     * Display a listing of the receipts.
     */
    public function index(Request $request)
    {
        $query = $this->buildReceiptQuery($request);
        
        // Calculate total sum of all receipts (before pagination, respects filters)
        // Clone query to avoid affecting pagination query
        $totalSum = (float) (clone $query)->sum('receipts.total');
        
        // Calculate status counts (respecting all filters except status)
        $baseQuery = $this->buildReceiptBaseQuery($request);
        $statusCounts = [
            'all' => (int) (clone $baseQuery)->count(),
            'pending' => (int) (clone $baseQuery)->where('receipts.status', 'pending')->count(),
            'paid' => (int) (clone $baseQuery)->where('receipts.status', 'paid')->count(),
        ];
        
        // Support limit and page parameters for pagination
        $perPage = $request->get('limit', 15);
        $page = $request->get('page', 1);
        $receipts = $query->paginate($perPage, ['*'], 'page', $page);

        $rows = $this->formatReceiptRows($receipts->getCollection());

        return response()->json([
            'rows' => $rows,
            'counts' => [
                'totalRows' => $receipts->total(),
                'totalPages' => $receipts->lastPage(),
                'all' => $statusCounts['all'],
                'pending' => $statusCounts['pending'],
                'paid' => $statusCounts['paid'],
            ],
            'totalSum' => number_format($totalSum, 2, '.', ''),
        ]);
    }

    /**
     * Display the specified receipt.
     */
    public function show(Receipt $receipt)
    {
        $receipt->load('user');
        $data = $this->formatReceiptDetails($receipt);

        return response()->json($data);
    }

    /**
     * Update the specified receipt in storage.
     */
    public function update(Request $request, Receipt $receipt)
    {
        $validated = $request->validate([
            'receipt_number' => 'sometimes|string|max:255|unique:receipts,receipt_number,' . $receipt->id,
            'user_id' => 'sometimes|exists:users,id',
            'total' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pending,paid,cancelled,refunded',
            'payment_method' => 'nullable|string|max:255',
            'receipt_date' => 'sometimes|date',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:vows,community_donations,external_donations,ascensions,online_donations,membership_fees,other',
        ]);

        $receipt->update($validated);
        $receipt->load('user');
        $data = $this->formatReceiptDetails($receipt);
        
        return response()->json($data);
    }

    /**
     * Remove the specified receipt from storage.
     */
    public function destroy(Receipt $receipt)
    {
        // Delete PDF file if exists
        if ($receipt->pdf_file && Storage::disk('public')->exists($receipt->pdf_file)) {
            Storage::disk('public')->delete($receipt->pdf_file);
        }
        
        $receipt->delete();
        return response()->json(null, 204);
    }

    /**
     * Download the PDF file for the specified receipt.
     */
    public function downloadPdf(Receipt $receipt)
    {
        if (!$receipt->pdf_file) {
            return response()->json([
                'message' => 'PDF file not found for this receipt'
            ], 404);
        }

        if (!Storage::disk('public')->exists($receipt->pdf_file)) {
            return response()->json([
                'message' => 'PDF file does not exist'
            ], 404);
        }

        $filePath = Storage::disk('public')->path($receipt->pdf_file);
        $filename = 'receipt_' . $receipt->receipt_number . '.pdf';
        
        return response()->download($filePath, $filename);
    }

    /**
     * Export receipts to Excel/CSV/PDF.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:receipts,id',
            'status' => 'nullable|string|in:pending,paid,cancelled,refunded',
            'type' => 'nullable|string|in:vows,community_donations,external_donations,ascensions,online_donations,membership_fees,other',
            'user_id' => 'nullable|integer|exists:users,id',
            'payment_method' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'file_type' => 'required|string|in:xls,csv,pdf',
        ]);

        // Build query based on filters
        if (!empty($validated['ids'])) {
            $query = Receipt::with('user')->whereIn('id', $validated['ids']);
        } else {
            $query = $this->buildReceiptQuery($request);
        }

        // Apply additional filters from validation
        if (!empty($validated['status'])) {
            $query->where('receipts.status', $validated['status']);
        }

        if (!empty($validated['type'])) {
            $query->where('receipts.type', $validated['type']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('receipts.user_id', $validated['user_id']);
        }

        if (!empty($validated['payment_method'])) {
            $query->where('receipts.payment_method', $validated['payment_method']);
        }

        if (!empty($validated['date_from'])) {
            $query->where('receipts.receipt_date', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->where('receipts.receipt_date', '<=', $validated['date_to']);
        }

        $receipts = $query->get();
        $fileType = $validated['file_type'];

        if ($fileType === 'pdf') {
            try {
                $rows = $this->formatReceiptRowsForPdf($receipts);
                // Ensure it's a Collection
                if (!$rows instanceof Collection) {
                    $rows = collect($rows);
                }
                
                // Handle empty collection
                if ($rows->isEmpty()) {
                    return response()->json([
                        'message' => 'No receipts found to export'
                    ], 404);
                }
                
                // Convert collection to array to ensure proper format
                $rowsArray = $rows->toArray();
                
                return Excel::download(new ReceiptsPdfExport(collect($rowsArray)), 'receipts.pdf', \Maatwebsite\Excel\Excel::MPDF);
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

        $rows = $this->formatReceiptRowsForExport($receipts);

        $writerType = match ($fileType) {
            'xls' => \Maatwebsite\Excel\Excel::XLSX,
            'csv' => \Maatwebsite\Excel\Excel::CSV,
        };

        $extension = $fileType === 'xls' ? 'xlsx' : $fileType;

        return Excel::download(new ReceiptsExport($rows), "receipts.{$extension}", $writerType);
    }

    /**
     * Build base query with filters (excluding status) for counting.
     */
    private function buildReceiptBaseQuery(Request $request)
    {
        $query = Receipt::where('business_id', current_business_id());

        // Filter by user_id
        if ($request->has('user_id') && $request->user_id !== null && $request->user_id !== '') {
            $query->where('receipts.user_id', $request->user_id);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== null && $request->type !== '') {
            $query->where('receipts.type', $request->type);
        }

        // Filter by payment_method
        if ($request->has('payment_method') && $request->payment_method !== null && $request->payment_method !== '') {
            $query->where('receipts.payment_method', $request->payment_method);
        }

        // Filter by date_from (start date)
        if ($request->has('date_from') && $request->date_from !== null && $request->date_from !== '') {
            $query->where('receipts.receipt_date', '>=', $request->date_from);
        }

        // Filter by date_to (end date)
        if ($request->has('date_to') && $request->date_to !== null && $request->date_to !== '') {
            $query->where('receipts.receipt_date', '<=', $request->date_to);
        }

        return $query;
    }

    /**
     * Build query with filters and sorting.
     */
    private function buildReceiptQuery(Request $request)
    {
        $query = Receipt::with('user');
        
        $needsUserJoin = false;

        // Filter by status
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('receipts.status', $request->status);
        }

        // Filter by user_id
        if ($request->has('user_id') && $request->user_id !== null && $request->user_id !== '') {
            $query->where('receipts.user_id', $request->user_id);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== null && $request->type !== '') {
            $query->where('receipts.type', $request->type);
        }

        // Filter by payment_method
        if ($request->has('payment_method') && $request->payment_method !== null && $request->payment_method !== '') {
            $query->where('receipts.payment_method', $request->payment_method);
        }

        // Filter by date_from (start date)
        if ($request->has('date_from') && $request->date_from !== null && $request->date_from !== '') {
            $query->where('receipts.receipt_date', '>=', $request->date_from);
        }

        // Filter by date_to (end date)
        if ($request->has('date_to') && $request->date_to !== null && $request->date_to !== '') {
            $query->where('receipts.receipt_date', '<=', $request->date_to);
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
                'total' => 'receipts.total',
                'receipt_date' => 'receipts.receipt_date',
                'status' => 'receipts.status',
                'type' => 'receipts.type',
            ];
            
            $dbColumn = $sortMap[$sortColumn] ?? 'receipts.created_at';
            $query->orderBy($dbColumn, $sortDirection);
        } else {
            // Validate sort_order
            if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            // Map sort_by values to database columns
            $sortMap = [
                'receipt_date' => 'receipts.receipt_date',
                'amount' => 'receipts.total',
                'total' => 'receipts.total',
                'total_amount' => 'receipts.total', // Backward compatibility
                'type' => 'receipts.type',
                'status' => 'receipts.status',
                'payment_method' => 'receipts.payment_method',
                'user' => 'users.name',
            ];

            $sortBy = $sortBy ?? 'created_at';
            
            // For user sorting, we need to join with users table
            if ($sortBy === 'user') {
                $needsUserJoin = true;
            } else {
                $dbColumn = $sortMap[$sortBy] ?? 'receipts.created_at';
                $query->orderBy($dbColumn, $sortOrder);
            }
        }

        // Apply joins if needed for user sorting
        if ($needsUserJoin) {
            $query->leftJoin('users', 'receipts.user_id', '=', 'users.id')
                  ->select('receipts.*')
                  ->orderBy('users.name', $sortOrder);
        }

        return $query;
    }

    /**
     * Format receipts for datatable rows.
     */
    private function formatReceiptRows($receipts)
    {
        return $receipts->map(function ($receipt) {
            // If receipt_number is empty/null, receipt_date should also be null
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
        });
    }

    /**
     * Format receipt details for API response.
     */
    private function formatReceiptDetails(Receipt $receipt)
    {
        // If receipt_number is empty/null, receipt_date should also be null
        $receiptNumber = $receipt->receipt_number ?: null;
        $receiptDate = $receiptNumber ? $receipt->receipt_date : null;
        
        return [
            'id' => (string)$receipt->id,
            'receiptNumber' => $receiptNumber,
            'userId' => $receipt->user_id ? (string)$receipt->user_id : null,
            'userName' => $receipt->user ? $receipt->user->name : null,
            'total' => number_format((float)$receipt->total, 2, '.', ''),
            'status' => $receipt->status,
            'paymentMethod' => $receipt->payment_method,
            'receiptDate' => $receiptDate,
            'description' => $receipt->description,
            'type' => $receipt->type,
            'createdAt' => $receipt->created_at,
            'updatedAt' => $receipt->updated_at,
        ];
    }

    /**
     * Format receipts for Excel/CSV export.
     */
    private function formatReceiptRowsForExport($receipts)
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

        return $receipts->map(function ($receipt) use ($typeLabels, $statusLabels) {
            return [
                $receipt->receipt_number ?? '',
                $receipt->user ? $receipt->user->name : '',
                number_format((float)$receipt->total, 2, '.', ''),
                $statusLabels[$receipt->status] ?? $receipt->status,
                $receipt->payment_method ?? '',
                $receipt->receipt_date ? $this->formatDateForResponse($receipt->receipt_date) : '',
                $receipt->description ?? '',
                $typeLabels[$receipt->type] ?? $receipt->type,
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

        // Reversed order for RTL PDF layout (matching headings)
        return $receipts->map(function ($receipt) use ($typeLabels, $statusLabels) {
            $date = $receipt->receipt_date ? $this->formatDateForResponse($receipt->receipt_date) : '';
            
            return [
                $typeLabels[$receipt->type] ?? ($receipt->type ?? ''),
                $receipt->description ?? '',
                $date ?? '',
                $receipt->payment_method ?? '',
                $statusLabels[$receipt->status] ?? ($receipt->status ?? ''),
                number_format((float)($receipt->total ?? 0), 2, '.', ''),
                $receipt->user ? ($receipt->user->name ?? '') : '',
                $receipt->receipt_number ?? '',
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
     * Get receipt statistics for dashboard.
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
        $monthlyTotal = (float) Receipt::whereBetween('receipt_date', [$startOfMonth, $endOfMonth])
            ->sum('total');

        // Category distribution
        $categoryDistribution = Receipt::whereBetween('receipt_date', [$startOfMonth, $endOfMonth])
            ->select('type', DB::raw('SUM(total) as total'))
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

        // Receipt trend for last N months
        $trendStart = $monthDate->copy()->subMonths($monthsBack - 1)->startOfMonth();
        $trend = [];
        
        for ($i = 0; $i < $monthsBack; $i++) {
            $currentMonth = $trendStart->copy()->addMonths($i);
            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();
            
            $monthTotal = (float) Receipt::whereBetween('receipt_date', [$monthStart, $monthEnd])
                ->sum('total');
            
            $trend[] = [
                'month' => $currentMonth->format('Y-m'),
                'amount' => number_format($monthTotal, 2, '.', ''),
            ];
        }

        // Uncollected receipts for the specified month (status = pending)
        $uncollectedTotal = (float) Receipt::whereBetween('receipt_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'pending')
            ->sum('total');
        
        $uncollectedPercentage = $monthlyTotal > 0 ? round(($uncollectedTotal / $monthlyTotal) * 100, 0) : 0;

        return response()->json([
            'monthlyTotal' => [
                'amount' => number_format($monthlyTotal, 2, '.', ''),
                'month' => $month,
                'currency' => '₪',
            ],
            'categoryDistribution' => $categoryDistribution,
            'trend' => $trend,
            'uncollectedReceipts' => [
                'total' => number_format($uncollectedTotal, 2, '.', ''),
                'percentage' => (int)$uncollectedPercentage,
                'month' => $month,
            ],
        ]);
    }

    /**
     * Get Hebrew label for receipt category.
     */
    private function getCategoryLabel(string $type): string
    {
        $labels = [
            'vows' => 'נדרים',
            'community_donations' => 'תרומות מהקהילה',
            'external_donations' => 'תרומות חיצוניות',
            'ascensions' => 'עליות',
            'online_donations' => 'תרומות אונליין',
            'membership_fees' => 'דמי חברים',
            'other' => 'אחר',
        ];

        return $labels[$type] ?? $type;
    }
}

