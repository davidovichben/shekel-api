<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Member;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Get a simple list of all groups with id and name.
     */
    public function list()
    {
        $groups = Group::select('id', 'name')
            ->where('business_id', current_business_id())
            ->orderBy('name')
            ->get();

        return response()->json($groups);
    }

    /**
     * Return all groups a given member is not part of.
     */
    public function index(int $memberId)
    {
        $member = Member::findOrFail($memberId);
        $memberGroupIds = $member->groups()->pluck('groups.id');

        $groups = Group::where('business_id', current_business_id())
            ->whereNotIn('id', $memberGroupIds)
            ->get();

        return response()->json($groups);
    }

    /**
     * Store a new group.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group = Group::create($validated);

        return response()->json($group, 201);
    }

    /**
     * Get a single group by ID.
     */
    public function show(int $groupId)
    {
        $group = Group::where('business_id', current_business_id())
            ->findOrFail($groupId);

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
        ]);
    }

    /**
     * Get all members in a group.
     */
    public function members(int $groupId)
    {
        $group = Group::where('business_id', current_business_id())
            ->findOrFail($groupId);

        $members = $group->members()->with('groups')->get();

        $formattedMembers = $members->map(function ($member) {
            return $this->formatMemberDetails($member);
        });

        return response()->json($formattedMembers);
    }

    /**
     * Update group name.
     */
    public function update(Request $request, int $groupId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $group = Group::where('business_id', current_business_id())
            ->findOrFail($groupId);

        $group->update(['name' => $validated['name']]);

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
        ]);
    }

    /**
     * Add a member to a group.
     */
    public function addMember(Request $request, int $groupId)
    {
        $validated = $request->validate([
            'member_id' => 'required|string',
        ]);

        $group = Group::where('business_id', current_business_id())
            ->findOrFail($groupId);

        $memberId = (int)$validated['member_id'];
        $member = Member::where('business_id', current_business_id())
            ->findOrFail($memberId);

        $group->members()->syncWithoutDetaching([$memberId]);

        return response()->json(['message' => 'Member added to group'], 201);
    }

    /**
     * Remove a member from a group.
     */
    public function removeMember(int $groupId, string $memberId)
    {
        $group = Group::where('business_id', current_business_id())
            ->findOrFail($groupId);

        $memberIdInt = (int)$memberId;
        $group->members()->detach($memberIdInt);

        return response()->json(null, 204);
    }

    /**
     * Format member details for API response.
     */
    private function formatMemberDetails(Member $member)
    {
        return [
            'id' => $member->id,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'full_name' => $member->full_name,
            'mobile' => $member->mobile,
            'phone' => $member->phone,
            'email' => $member->email,
            'address' => $member->address,
            'address_2' => $member->address_2,
            'city' => $member->city,
            'country' => $member->country,
            'zipcode' => $member->zipcode,
            'gregorian_birth_date' => $member->gregorian_birth_date,
            'hebrew_birth_date' => $member->hebrew_birth_date,
            'gregorian_wedding_date' => $member->gregorian_wedding_date,
            'hebrew_wedding_date' => $member->hebrew_wedding_date,
            'gregorian_death_date' => $member->gregorian_death_date,
            'hebrew_death_date' => $member->hebrew_death_date,
            'contact_person' => $member->contact_person,
            'contact_person_type' => $member->contact_person_type,
            'tag' => $member->tag,
            'title' => $member->title,
            'type' => $member->type,
            'member_number' => $member->member_number,
            'has_website_account' => $member->has_website_account,
            'should_mail' => $member->should_mail,
            'balance' => $member->balance,
            'last_message_date' => $member->last_message_date,
            'groups' => $member->groups->pluck('name'),
            'created_at' => $member->created_at,
            'updated_at' => $member->updated_at,
        ];
    }
}
