<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DebtController extends Controller
{
    /**
     * Display a listing of the debts.
     */
    public function index(Request $request)
    {
        $query = $this->buildDebtQuery($request);
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

    /**
     * Store a newly created debt in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:500',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,paid,overdue,cancelled',
        ]);

        $debt = Debt::create($validated);
        return response()->json($debt, 201);
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
        $validated = $request->validate([
            'member_id' => 'sometimes|exists:members,id',
            'amount' => 'sometimes|numeric',
            'description' => 'nullable|string|max:500',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:pending,paid,overdue,cancelled',
        ]);

        $debt->update($validated);
        return response()->json($debt);
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
            'dueDate' => 'due_date',
            'status' => 'status',
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

        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = str_starts_with($sortColumn, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sortColumn, '-');

        $sortMap = [
            'amount' => 'amount',
            'dueDate' => 'due_date',
            'status' => 'status',
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
                'amount' => $debt->amount,
                'description' => $debt->description,
                'dueDate' => $debt->due_date,
                'status' => $debt->status,
            ];
        });
    }

    private function formatDebtDetails(Debt $debt)
    {
        return [
            'id' => $debt->id,
            'memberId' => $debt->member_id,
            'memberName' => $debt->member ? $debt->member->full_name : null,
            'amount' => $debt->amount,
            'description' => $debt->description,
            'dueDate' => $debt->due_date,
            'status' => $debt->status,
            'createdAt' => $debt->created_at,
            'updatedAt' => $debt->updated_at,
        ];
    }
}