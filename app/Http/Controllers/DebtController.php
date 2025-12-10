<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $totalSum = (float) (clone $query)->sum('amount');
        
        // Support limit parameter for pagination
        $perPage = $request->get('limit', 15);
        $debts = $query->paginate($perPage);

        $rows = $this->formatDebtRows($debts->getCollection());

        return response()->json([
            'rows' => $rows,
            'counts' => [
                'totalRows' => $debts->total(),
                'totalPages' => $debts->lastPage(),
                'currentPage' => $debts->currentPage(),
                'perPage' => $debts->perPage(),
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

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
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
            return null;
        }

        try {
            if ($date instanceof \Carbon\Carbon) {
                return $date->format('d/m/Y');
            }
            
            if (is_string($date)) {
                $carbon = \Carbon\Carbon::parse($date);
                return $carbon->format('d/m/Y');
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
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