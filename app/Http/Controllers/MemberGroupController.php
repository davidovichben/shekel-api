<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

class MemberGroupController extends Controller
{
    /**
     * Display a listing of the member's groups.
     */
    public function index(int $memberId)
    {
        $member = Member::findOrFail($memberId);
        $groups = $member->groups()->get();

        return response()->json($groups);
    }

    /**
     * Add a member to a group.
     */
    public function store(Request $request, int $memberId)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);

        $member = Member::findOrFail($memberId);
        $member->groups()->syncWithoutDetaching([$validated['group_id']]);

        return response()->json(['message' => 'Member added to group'], 201);
    }

    /**
     * Remove a member from a group.
     */
    public function destroy(int $memberId, int $groupId)
    {
        $member = Member::findOrFail($memberId);
        $member->groups()->detach($groupId);

        return response()->json(null, 204);
    }
}
