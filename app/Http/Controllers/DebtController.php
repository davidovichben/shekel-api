<?php

namespace App\Http\Controllers;

use App\Exports\DebtsExport;
use App\Exports\DebtsPdfExport;
use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class DebtController extends Controller
{
    /**
     * Display a listing of the debts.
     */
    public function index(Request $request)
    {
        $query = $this->buildDebtQuery($request);
        
        // Calculate total sum of all debts (before pagination, respects filters)
        // Clone query to avoid affecting pagination query
        $totalSum = (float) (clone $query)->sum('debts.amount');
        
        // Support limit and page parameters for pagination
        $perPage = $request->get('limit', 15);
        $page = $request->get('page', 1);
        $debts = $query->paginate($perPage, ['*'], 'page', $page);

        $rows = $this->formatDebtRows($debts->getCollection());

        return response()->json([
            'rows' => $rows,
            'counts' => [
                'totalRows' => $debts->total(),
                'totalPages' => $debts->lastPage(),
            ],
            'totalSum' => number_format($totalSum, 2, '.', ''),
        ]);
    }

    /**
     * Bulk create multiple debts in a single transaction.
     */
    public function bulkStore(Request $request)
    {
        $debtsData = $request->validate([
            '*' => 'required|array',
            '*.memberId' => 'required',
            '*.debtType' => 'nullable',
            '*.amount' => 'required|numeric',
            '*.description' => 'nullable|string|max:500',
            '*.gregorianDate' => 'nullable',
            '*.sendImmediateReminder' => 'nullable|boolean',
            '*.status' => 'nullable',
        ]);

        $createdDebts = [];

        // Use database transaction to ensure all or nothing
        DB::beginTransaction();
        
        try {
            foreach ($debtsData as $debtData) {
                // Handle gregorianDate -> due_date mapping
                $requestData = $debtData;
                if (isset($requestData['gregorianDate']) && !empty($requestData['gregorianDate'])) {
                    $dateValue = $requestData['gregorianDate'];
                    $parsedDate = $this->parseDate($dateValue);
                    if ($parsedDate) {
                        $requestData['due_date'] = $parsedDate;
                    }
                    unset($requestData['gregorianDate']);
                }

                // Handle debtType -> type mapping
                if (isset($requestData['debtType'])) {
                    $requestData['type'] = $requestData['debtType'];
                    unset($requestData['debtType']);
                }

                // Handle lastReminderSentAt -> last_reminder_sent_at mapping
                if (isset($requestData['lastReminderSentAt']) && !empty($requestData['lastReminderSentAt'])) {
                    $dateValue = $requestData['lastReminderSentAt'];
                    $parsedDate = $this->parseDate($dateValue);
                    if ($parsedDate) {
                        $requestData['last_reminder_sent_at'] = $parsedDate;
                    }
                    unset($requestData['lastReminderSentAt']);
                }

                // Normalize camelCase to snake_case
                $data = $this->normalizeRequestData($requestData);

                // Set default status if not provided
                if (!isset($data['status'])) {
                    $data['status'] = 'pending';
                }

                // Set default type if not provided
                if (!isset($data['type']) || empty($data['type'])) {
                    $data['type'] = 'other';
                }

                // Handle send immediate reminder option
                $sendImmediateReminder = false;
                if (isset($debtData['sendImmediateReminder']) || isset($debtData['send_immediate_reminder'])) {
                    $sendImmediateReminder = filter_var(
                        $debtData['sendImmediateReminder'] ?? $debtData['send_immediate_reminder'] ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    );
                }

                $validated = validator($data, [
                    'member_id' => 'required|exists:members,id',
                    'type' => 'required|in:neder_shabbat,tikun_nezek,dmei_chaver,kiddush,neder_yom_shabbat,other',
                    'amount' => 'required|numeric',
                    'description' => 'nullable|string|max:500',
                    'due_date' => 'nullable|date',
                    'status' => 'nullable|in:pending,paid,overdue,cancelled',
                    'last_reminder_sent_at' => 'nullable|date',
                ])->validate();

                // Set last_reminder_sent_at if immediate reminder requested
                if ($sendImmediateReminder) {
                    $validated['last_reminder_sent_at'] = now();
                }

                $debt = Debt::create($validated);
                $debt->load('member');
                $createdDebts[] = $this->formatDebtDetails($debt);
            }

            DB::commit();

            return response()->json($createdDebts, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create debts',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Store a newly created debt in storage.
     */
    public function store(Request $request)
    {
        // Handle gregorianDate -> due_date mapping
        $requestData = $request->all();
        if (isset($requestData['gregorianDate']) && !empty($requestData['gregorianDate'])) {
            $dateValue = $requestData['gregorianDate'];
            // Parse date formats: DD/MM/YY, DD/MM/YYYY, or YYYY-MM-DD
            $parsedDate = $this->parseDate($dateValue);
            if ($parsedDate) {
                $requestData['due_date'] = $parsedDate;
            }
            unset($requestData['gregorianDate']);
        }
        
        // Handle debtType -> type mapping
        if (isset($requestData['debtType'])) {
            $requestData['type'] = $requestData['debtType'];
            unset($requestData['debtType']);
        }
        
        // Handle lastReminderSentAt -> last_reminder_sent_at mapping
        if (isset($requestData['lastReminderSentAt']) && !empty($requestData['lastReminderSentAt'])) {
            $dateValue = $requestData['lastReminderSentAt'];
            // Parse date formats: DD/MM/YY, DD/MM/YYYY, or YYYY-MM-DD
            $parsedDate = $this->parseDate($dateValue);
            if ($parsedDate) {
                $requestData['last_reminder_sent_at'] = $parsedDate;
            }
            unset($requestData['lastReminderSentAt']);
        }
        
        // Normalize camelCase to snake_case
        $data = $this->normalizeRequestData($requestData);
        
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }
        
        // Set default type if not provided
        if (!isset($data['type']) || empty($data['type'])) {
            $data['type'] = 'other';
        }
        
        // Handle send immediate reminder option
        $sendImmediateReminder = false;
        if (isset($requestData['sendImmediateReminder']) || isset($requestData['send_immediate_reminder'])) {
            $sendImmediateReminder = filter_var(
                $requestData['sendImmediateReminder'] ?? $requestData['send_immediate_reminder'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            );
        }
        
        $validated = validator($data, [
            'member_id' => 'required|exists:members,id',
            'type' => 'required|in:neder_shabbat,tikun_nezek,dmei_chaver,kiddush,neder_yom_shabbat,other',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:500',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,paid,overdue,cancelled',
            'last_reminder_sent_at' => 'nullable|date',
        ])->validate();

        // Set last_reminder_sent_at if immediate reminder requested
        if ($sendImmediateReminder) {
            $validated['last_reminder_sent_at'] = now();
        }

        $debt = Debt::create($validated);
        $debt->load('member');
        $data = $this->formatDebtDetails($debt);
        
        return response()->json($data, 201);
    }

    /**
     * Display the specified debt.
     */
    public function show(Debt $debt)
    {
        $debt->load('member');
        $data = $this->formatDebtDetails($debt);

        return response()->json($data);
    }

    /**
     * Update the specified debt in storage.
     */
    public function update(Request $request, Debt $debt)
    {
        // Handle gregorianDate -> due_date mapping
        $requestData = $request->all();
        if (isset($requestData['gregorianDate']) && !empty($requestData['gregorianDate'])) {
            $dateValue = $requestData['gregorianDate'];
            // Parse date formats: DD/MM/YY, DD/MM/YYYY, or YYYY-MM-DD
            $parsedDate = $this->parseDate($dateValue);
            if ($parsedDate) {
                $requestData['due_date'] = $parsedDate;
            }
            unset($requestData['gregorianDate']);
        }
        
        // Handle debtType -> type mapping
        if (isset($requestData['debtType'])) {
            $requestData['type'] = $requestData['debtType'];
            unset($requestData['debtType']);
        }
        
        // Handle lastReminderSentAt -> last_reminder_sent_at mapping
        if (isset($requestData['lastReminderSentAt']) && !empty($requestData['lastReminderSentAt'])) {
            $dateValue = $requestData['lastReminderSentAt'];
            // Parse date formats: DD/MM/YY, DD/MM/YYYY, or YYYY-MM-DD
            $parsedDate = $this->parseDate($dateValue);
            if ($parsedDate) {
                $requestData['last_reminder_sent_at'] = $parsedDate;
            }
            unset($requestData['lastReminderSentAt']);
        }
        
        // Normalize camelCase to snake_case
        $data = $this->normalizeRequestData($requestData);
        
        $validated = validator($data, [
            'member_id' => 'sometimes|exists:members,id',
            'type' => 'nullable|in:neder_shabbat,tikun_nezek,dmei_chaver,kiddush,neder_yom_shabbat,other',
            'amount' => 'sometimes|numeric',
            'description' => 'nullable|string|max:500',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:pending,paid,overdue,cancelled',
            'last_reminder_sent_at' => 'nullable|date',
        ])->validate();

        $debt->update($validated);
        $debt->load('member');
        $data = $this->formatDebtDetails($debt);
        
        return response()->json($data);
    }

    /**
     * Remove the specified debt from storage.
     */
    public function destroy(Debt $debt)
    {
        $debt->delete();
        return response()->json(null, 204);
    }

    /**
     * Send a reminder for the specified debt.
     * Updates the last_reminder_sent_at timestamp.
     */
    public function sendReminder(Debt $debt)
    {
        $debt->update([
            'last_reminder_sent_at' => now(),
        ]);

        $debt->load('member');
        $data = $this->formatDebtDetails($debt);

        return response()->json($data);
    }

    /**
     * Export debts to Excel/CSV.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:debts,id',
            'status' => 'nullable|string|in:pending,paid,overdue,cancelled',
            'type' => 'nullable|string|in:neder_shabbat,tikun_nezek,dmei_chaver,kiddush,neder_yom_shabbat,other',
            'member_id' => 'nullable|integer|exists:members,id',
            'file_type' => 'required|string|in:xls,csv,pdf',
        ]);

        // Build query based on filters
        if (!empty($validated['ids'])) {
            $query = Debt::with('member')->whereIn('id', $validated['ids']);
        } else {
            $query = $this->buildDebtQuery($request);
        }

        // Apply additional filters
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['member_id'])) {
            $query->where('member_id', $validated['member_id']);
        }

        $debts = $query->get();
        $fileType = $validated['file_type'];

        if ($fileType === 'pdf') {
            try {
                $rows = $this->formatDebtRowsForPdf($debts);
                // Ensure it's a Collection
                if (!$rows instanceof Collection) {
                    $rows = collect($rows);
                }
                
                // Handle empty collection
                if ($rows->isEmpty()) {
                    return response()->json([
                        'message' => 'No debts found to export'
                    ], 404);
                }
                
                // Convert collection to array to ensure proper format
                $rowsArray = $rows->toArray();
                
                return Excel::download(new DebtsPdfExport(collect($rowsArray)), 'debts.pdf', \Maatwebsite\Excel\Excel::MPDF);
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

        $rows = $this->formatDebtRowsForExport($debts);

        $writerType = match ($fileType) {
            'xls' => \Maatwebsite\Excel\Excel::XLSX,
            'csv' => \Maatwebsite\Excel\Excel::CSV,
        };

        $extension = $fileType === 'xls' ? 'xlsx' : $fileType;

        return Excel::download(new DebtsExport($rows), "debts.{$extension}", $writerType);
    }

    /**
     * Display a paginated listing of debts for a specific member.
     */
    public function byMember(Request $request, int $memberId, string $status = 'open')
    {
        $query = Debt::with('member')
            ->where('member_id', $memberId);

        if ($status === 'closed') {
            $query->whereIn('status', ['paid', 'cancelled']);
        } else {
            $query->whereIn('status', ['pending', 'overdue']);
        }

        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = str_starts_with($sortColumn, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sortColumn, '-');

        $sortMap = [
            'amount' => 'amount',
            'gregorianDate' => 'due_date',
            'dueDate' => 'due_date', // Support both for backward compatibility
            'status' => 'status',
            'type' => 'type',
        ];

        $dbColumn = $sortMap[$sortColumn] ?? 'created_at';
        $query->orderBy($dbColumn, $sortDirection);

        $debts = $query->paginate(15);
        $rows = $this->formatDebtRows($debts->getCollection());

        return response()->json([
            'rows' => $rows,
            'counts' => [
                'totalRows' => $debts->total(),
                'totalPages' => $debts->lastPage(),
            ],
        ]);
    }

    private function buildDebtQuery(Request $request)
    {
        $query = Debt::with('member');
        
        $needsMemberJoin = false;
        $needsBillingJoin = false;

        // Filter by status
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('debts.status', $request->status);
        }

        // Filter by member_id (backward compatibility)
        if ($request->has('member_id')) {
            $query->where('debts.member_id', $request->member_id);
        }

        // Filter by type (backward compatibility)
        if ($request->has('type')) {
            $query->where('debts.type', $request->type);
        }

        // Filter by date_from (start date)
        if ($request->has('date_from') && $request->date_from !== null && $request->date_from !== '') {
            $query->where('debts.due_date', '>=', $request->date_from);
        }

        // Filter by date_to (end date)
        if ($request->has('date_to') && $request->date_to !== null && $request->date_to !== '') {
            $query->where('debts.due_date', '<=', $request->date_to);
        }

        // Filter by should_bill (automatic payment approval)
        if ($request->has('should_bill')) {
            $shouldBill = filter_var($request->should_bill, FILTER_VALIDATE_BOOLEAN);
            if ($shouldBill) {
                $needsBillingJoin = true;
            }
        }

        // Handle sorting - check if we need member join for name sorting
        $sortBy = $request->get('sort_by');
        if ($sortBy === 'name') {
            $needsMemberJoin = true;
        }
        
        // Also check legacy sort parameter
        if (!$sortBy && $request->has('sort')) {
            $sortColumn = ltrim($request->get('sort', 'created_at'), '-');
            // Legacy sort doesn't support name, so no need to check
        }

        // Apply joins if needed
        if ($needsBillingJoin) {
            $query->join('member_billing_settings', 'debts.member_id', '=', 'member_billing_settings.member_id')
                  ->where('member_billing_settings.should_bill', true);
        }
        
        if ($needsMemberJoin) {
            $query->join('members', 'debts.member_id', '=', 'members.id');
        }
        
        // Select only debts columns to avoid conflicts when joins are used
        if ($needsBillingJoin || $needsMemberJoin) {
            $query->select('debts.*');
        }

        // Handle sorting
        if ($request->has('sort') && !$request->has('sort_by')) {
            // Legacy sort parameter
            $sortColumn = $request->get('sort', 'created_at');
            $sortDirection = str_starts_with($sortColumn, '-') ? 'desc' : 'asc';
            $sortColumn = ltrim($sortColumn, '-');
            
            $sortMap = [
                'amount' => 'debts.amount',
                'gregorianDate' => 'debts.due_date',
                'dueDate' => 'debts.due_date',
                'status' => 'debts.status',
                'type' => 'debts.type',
            ];
            
            $dbColumn = $sortMap[$sortColumn] ?? 'debts.created_at';
            $query->orderBy($dbColumn, $sortDirection);
        } else {
            // New sort_by and sort_order parameters
            $sortBy = $sortBy ?? 'created_at';
            $sortOrder = $request->get('sort_order', 'desc');
            
            // Validate sort_order
            if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            // Map sort_by values to database columns
            $sortMap = [
                'date' => 'debts.due_date',
                'amount' => 'debts.amount',
                'name' => 'members.first_name',
            ];

            $dbColumn = $sortMap[$sortBy] ?? 'debts.created_at';
            
            // For name sorting, sort by first_name and last_name
            if ($sortBy === 'name') {
                $query->orderBy('members.first_name', $sortOrder)
                      ->orderBy('members.last_name', $sortOrder);
            } else {
                $query->orderBy($dbColumn, $sortOrder);
            }
        }

        return $query;
    }

    private function formatDebtRows($debts)
    {
        return $debts->map(function ($debt) {
            return [
                'id' => $debt->id,
                'memberId' => $debt->member_id,
                'memberName' => $debt->member ? $debt->member->full_name : null,
                'type' => $debt->type,
                'amount' => $debt->amount,
                'description' => $debt->description,
                'gregorianDate' => $debt->due_date,
                'status' => $debt->status,
                'lastReminderSentAt' => $debt->last_reminder_sent_at,
            ];
        });
    }

    private function formatDebtRowsForExport($debts)
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
            return [
                $debt->member ? $debt->member->full_name : '',
                $typeLabels[$debt->type] ?? $debt->type,
                number_format((float)$debt->amount, 2, '.', ''),
                $debt->description ?? '',
                $debt->due_date ? $this->formatDateForResponse($debt->due_date) : '',
                $statusLabels[$debt->status] ?? $debt->status,
                $debt->last_reminder_sent_at ? $this->formatDateForResponse($debt->last_reminder_sent_at) : '',
            ];
        });
    }

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

        // Reversed order for RTL PDF layout (matching headings)
        return $debts->map(function ($debt) use ($typeLabels, $statusLabels) {
            $lastReminder = $debt->last_reminder_sent_at ? $this->formatDateForResponse($debt->last_reminder_sent_at) : '';
            $dueDate = $debt->due_date ? $this->formatDateForResponse($debt->due_date) : '';
            
            return [
                $lastReminder ?? '',
                $statusLabels[$debt->status] ?? ($debt->status ?? ''),
                $dueDate ?? '',
                $debt->description ?? '',
                number_format((float)($debt->amount ?? 0), 2, '.', ''),
                $typeLabels[$debt->type] ?? ($debt->type ?? ''),
                $debt->member ? ($debt->member->full_name ?? '') : '',
            ];
        });
    }

    private function formatDebtDetails(Debt $debt)
    {
        return [
            'id' => (string)$debt->id,
            'memberId' => (string)$debt->member_id,
            'memberName' => $debt->member ? $debt->member->full_name : null,
            'debtType' => $debt->type,
            'amount' => $debt->amount,
            'description' => $debt->description,
            'gregorianDate' => $this->formatDateForResponse($debt->due_date),
            'status' => $debt->status,
            'lastReminderSentAt' => $this->formatDateForResponse($debt->last_reminder_sent_at),
            'createdAt' => $debt->created_at,
            'updatedAt' => $debt->updated_at,
        ];
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
     * Parse date from various formats to Y-m-d format.
     * Supports: DD/MM/YY, DD/MM/YYYY, YYYY-MM-DD
     * Assumes DD/MM/YYYY format (not MM/DD/YYYY)
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        // Try to parse DD/MM/YY or DD/MM/YYYY format
        // Format: DD/MM/YYYY or DD/MM/YY (European format)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $dateString, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];
            
            // Handle 2-digit year (assume 2000-2099)
            if ($year < 100) {
                $year = $year < 50 ? 2000 + $year : 1900 + $year;
            }
            
            // Validate date (day, month, year)
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        
        // Try to parse YYYY-MM-DD format (already valid)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }
        
        // Try Carbon parsing with explicit DD/MM/YYYY format
        try {
            // Try DD/MM/YYYY format first
            $date = \Carbon\Carbon::createFromFormat('d/m/Y', $dateString);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Try DD/MM/YY format
            try {
                $date = \Carbon\Carbon::createFromFormat('d/m/y', $dateString);
                return $date->format('Y-m-d');
            } catch (\Exception $e2) {
                // Fallback to Carbon's default parsing
                try {
                    return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
                } catch (\Exception $e3) {
                    return null;
                }
            }
        }
    }
}