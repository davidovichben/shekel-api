<?php

namespace App\Http\Controllers;

use App\Exports\MembersExport;
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
            ->mapWithKeys(fn ($count, $type) => [Str::camel($type) => $count])
            ->toArray();

        $rows = $this->formatMemberRows($members->getCollection());

        return response()->json([
            'rows' => $rows,
            'counts' => [
                'types' => $typeCounts,
                'totalRows' => $members->total(),
                'totalPages' => $members->lastPage(),
            ],
        ]);
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
            'gender' => 'nullable|in:male,female,other',
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
            'gender' => 'nullable|in:male,female,other',
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


    public function export(Request $request)
    {
        $query = $this->buildMemberQuery($request);
        $members = $query->get();

        $rows = $this->formatMemberRows($members, true);

        return Excel::download(new MembersExport($rows), 'members.xlsx');
    }


    private function buildMemberQuery(Request $request)
    {
        $query = Member::with('groups');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = str_starts_with($sortColumn, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sortColumn, '-');

        $sortMap = [
            'fullName' => 'first_name',
            'type' => 'type',
            'balance' => 'balance',
            'mobile' => 'mobile',
            'lastMessageDate' => 'last_message_date',
        ];

        $dbColumn = $sortMap[$sortColumn] ?? 'created_at';
        $query->orderBy($dbColumn, $sortDirection);

        return $query;
    }

    private function formatMemberRows($members, $forExport = false)
    {
        return $members->map(function ($member) use ($forExport) {
            return [
                "id"    => $member->id,
                'fullName' => $member->full_name,
                'type' => $member->type ? Str::camel($member->type) : null,
                'balance' => $member->balance,
                'mobile' => $member->mobile,
                'lastMessageDate' => $member->last_message_date,
                'groups' => $forExport
                    ? $member->groups->pluck('name')->implode(', ')
                    : $member->groups->pluck('name'),
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
            'gender' => $member->gender,
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
