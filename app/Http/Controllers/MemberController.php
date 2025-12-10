<?php

namespace App\Http\Controllers;

use App\Exports\MembersExport;
use App\Exports\MembersPdfExport;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class MemberController extends Controller
{
    /**
     * Display a listing of the members.
     */
    public function index(Request $request)
    {
        $query = $this->buildMemberQuery($request);
        $members = $query->paginate(15);

        $typeCounts = Member::selectRaw('type, COUNT(*) as count')
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $rows = $this->formatMemberRows($members->getCollection());

        return response()->json([
            'rows' => $rows,
            'counts' => [
                'types' => $typeCounts,
                'total_rows' => $members->total(),
                'total_pages' => $members->lastPage(),
            ],
        ]);
    }

    /**
     * Get a simple list of members with only id and name.
     */
    public function list(Request $request)
    {
        $query = Member::select('id', 'first_name', 'last_name');

        if ($request->has('search')) {
            $name = $request->search;
            $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$name}%"]);
        }

        $members = $query->orderBy('first_name')->limit(30)->get();

        return response()->json($members->map(fn ($member) => [
            'id' => $member->id,
            'name' => trim("{$member->first_name} {$member->last_name}"),
        ]));
    }

    /**
     * Store a newly created member in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'address_2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'zipcode' => 'nullable|string|max:20',
            'gregorian_birth_date' => 'nullable|date',
            'hebrew_birth_date' => 'nullable|string|max:255',
            'gregorian_wedding_date' => 'nullable|date',
            'hebrew_wedding_date' => 'nullable|string|max:255',
            'gregorian_death_date' => 'nullable|date',
            'hebrew_death_date' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_type' => 'nullable|in:child,parent,sibling,spouse,brother-in-law,grandparent,son-in-law,guest,phone_operator,other',
            'tag' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:permanent,family_member,guest,supplier,other,primary_admin',
            'member_number' => 'nullable|string|unique:members|max:255',
            'has_website_account' => 'boolean',
            'should_mail' => 'boolean',
        ]);

        $member = Member::create($validated);
        return response()->json($member, 201);
    }

    /**
     * Display the specified member.
     */
    public function show(Member $member)
    {
        $member->load('groups');
        $data = $this->formatMemberDetails($member);

        return response()->json($data);
    }

    /**
     * Update the specified member in storage.
     */
    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'address_2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'zipcode' => 'nullable|string|max:20',
            'gregorian_birth_date' => 'nullable|date',
            'hebrew_birth_date' => 'nullable|string|max:255',
            'gregorian_wedding_date' => 'nullable|date',
            'hebrew_wedding_date' => 'nullable|string|max:255',
            'gregorian_death_date' => 'nullable|date',
            'hebrew_death_date' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_type' => 'nullable|in:child,parent,sibling,spouse,brother-in-law,grandparent,son-in-law,guest,phone_operator,other',
            'tag' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'type' => 'sometimes|in:permanent,family_member,guest,supplier,other,primary_admin',
            'member_number' => 'nullable|string|unique:members,member_number,' . $member->id . '|max:255',
            'has_website_account' => 'boolean',
            'should_mail' => 'boolean',
        ]);

        $member->update($validated);
        return response()->json($member);
    }

    /**
     * Remove the specified member from storage.
     */
    public function destroy(Member $member)
    {
        $member->delete();
        return response()->json(null, 204);
    }

    /**
     * Remove multiple members from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:members,id',
        ]);

        Member::whereIn('id', $validated['ids'])->delete();

        return response()->json(null, 204);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:members,id',
            'type' => 'nullable|string|in:permanent,family_member,guest,supplier,other,primary_admin,secondary_admin',
            'file_type' => 'required|string|in:pdf,xls,csv',
        ]);

        if (!empty($validated['ids'])) {
            $query = Member::with('groups')->whereIn('id', $validated['ids']);
        } else {
            $query = $this->buildMemberQuery($request);
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $members = $query->get();
        $fileType = $validated['file_type'];

        if ($fileType === 'pdf') {
            $rows = $this->formatMemberRowsForPdf($members);
            return Excel::download(new MembersPdfExport($rows), 'members.pdf', \Maatwebsite\Excel\Excel::MPDF);
        }

        $rows = $this->formatMemberRows($members, true);

        $writerType = match ($fileType) {
            'xls' => \Maatwebsite\Excel\Excel::XLSX,
            'csv' => \Maatwebsite\Excel\Excel::CSV,
        };

        $extension = $fileType === 'xls' ? 'xlsx' : $fileType;

        return Excel::download(new MembersExport($rows), "members.{$extension}", $writerType);
    }

    private function formatMemberRowsForPdf($members)
    {
        $typeLabels = [
            'permanent' => 'קבוע',
            'family_member' => 'בן משפחה',
            'guest' => 'אורח',
            'supplier' => 'ספק',
            'other' => 'אחר',
            'primary_admin' => 'מנהל ראשי',
            'secondary_admin' => 'מנהל משני',
        ];

        return $members->map(function ($member) use ($typeLabels) {
            return [
                $member->groups->pluck('name')->implode(', '),
                $member->last_message_date,
                $member->mobile,
                $member->balance,
                $typeLabels[$member->type] ?? $member->type,
                $member->full_name,
            ];
        });
    }


    private function buildMemberQuery(Request $request)
    {
        $query = Member::with('groups');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%");
            });
        }

        $sortColumn = $request->get('sortBy', $request->get('sort', 'id'));
        $sortColumn = ltrim($sortColumn, '-');
        $sortDirection = $request->get('sortOrder', 'desc');

        $sortMap = [
            'id' => 'id',
            'fullName' => 'first_name',
            'type' => 'type',
            'balance' => 'balance',
            'mobile' => 'mobile',
            'lastMessageDate' => 'last_message_date',
        ];

        $dbColumn = $sortMap[$sortColumn] ?? 'id';
        $query->orderBy($dbColumn, $sortDirection);

        return $query;
    }

    private function formatMemberRows($members, $forExport = false)
    {
        $typeLabels = [
            'permanent' => 'קבוע',
            'family_member' => 'בן משפחה',
            'guest' => 'אורח',
            'supplier' => 'ספק',
            'other' => 'אחר',
            'primary_admin' => 'מנהל ראשי',
            'secondary_admin' => 'מנהל משני',
        ];

        return $members->map(function ($member) use ($forExport, $typeLabels) {
            if ($forExport) {
                return [
                    $member->full_name,
                    $typeLabels[$member->type] ?? $member->type,
                    $member->balance,
                    $member->mobile,
                    $member->last_message_date,
                    $member->groups->pluck('name')->implode(', '),
                ];
            }

            return [
                'id' => $member->id,
                'fullName' => $member->full_name,
                'type' => $member->type,
                'balance' => $member->balance,
                'mobile' => $member->mobile,
                'lastMessageDate' => $member->last_message_date,
                'groups' => $member->groups->pluck('name'),
            ];
        });
    }

    private function formatMemberDetails(Member $member)
    {
        return [
            'id' => $member->id,
            'firstName' => $member->first_name,
            'lastName' => $member->last_name,
            'fullName' => $member->full_name,
            'mobile' => $member->mobile,
            'phone' => $member->phone,
            'email' => $member->email,
            'address' => $member->address,
            'address2' => $member->address_2,
            'city' => $member->city,
            'country' => $member->country,
            'zipcode' => $member->zipcode,
            'gregorianBirthDate' => $member->gregorian_birth_date,
            'hebrewBirthDate' => $member->hebrew_birth_date,
            'gregorianWeddingDate' => $member->gregorian_wedding_date,
            'hebrewWeddingDate' => $member->hebrew_wedding_date,
            'gregorianDeathDate' => $member->gregorian_death_date,
            'hebrewDeathDate' => $member->hebrew_death_date,
            'contactPerson' => $member->contact_person,
            'contactPersonType' => $member->contact_person_type,
            'tag' => $member->tag,
            'title' => $member->title,
            'type' => $member->type ? Str::camel($member->type) : null,
            'memberNumber' => $member->member_number,
            'hasWebsiteAccount' => $member->has_website_account,
            'shouldMail' => $member->should_mail,
            'balance' => $member->balance,
            'lastMessageDate' => $member->last_message_date,
            'groups' => $member->groups->pluck('name'),
            'createdAt' => $member->created_at,
            'updatedAt' => $member->updated_at,
        ];
    }
}
