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
        $groups = Group::select('id', 'name')->orderBy('name')->get();

        return response()->json($groups);
    }

    /**
     * Return all groups a given member is not part of.
     */
    public function index(int $memberId)
    {
        $member = Member::findOrFail($memberId);
        $memberGroupIds = $member->groups()->pluck('groups.id');

        $groups = Group::whereNotIn('id', $memberGroupIds)->get();

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
}
