<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = \App\Models\Department::withCount('users')->get();
        return view('departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        \App\Models\Department::create($request->only('name', 'description'));
        return back()->with('success', 'Department created successfully.');
    }

    public function show($id)
    {
        $department = \App\Models\Department::with('users')->findOrFail($id);

        // Get users NOT in any department (or maybe just not in THIS department? Usually per user 1 department)
        // Basic logic: Users who don't have a department_id
        $availableUsers = \App\Models\DeviceUser::whereNull('department_id')
            ->orderBy('name')
            ->get();

        return view('departments.show', compact('department', 'availableUsers'));
    }

    public function addUsers(Request $request, $id)
    {
        $department = \App\Models\Department::findOrFail($id);

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:device_users,id'
        ]);

        \App\Models\DeviceUser::whereIn('id', $request->user_ids)->update(['department_id' => $department->id]);

        return back()->with('success', 'Users added to department successfully.');
    }

    public function removeUser($id, $userId)
    {
        $user = \App\Models\DeviceUser::where('department_id', $id)->findOrFail($userId);
        $user->update(['department_id' => null]);

        return back()->with('success', 'User removed from department.');
    }

    public function destroy($id)
    {
        $department = \App\Models\Department::findOrFail($id);
        // Reset users
        $department->users()->update(['department_id' => null]);
        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }
}
