<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = \App\Models\Permission::with('user')->orderBy('date', 'desc')->paginate(10);
        $users = \App\Models\DeviceUser::pluck('name', 'user_id_on_device');
        return view('permissions.index', compact('permissions', 'users'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id_on_device' => 'required',
            'date' => 'required|date',
        ]);

        \App\Models\Permission::create($request->all());

        return redirect()->route('permissions.index')->with('success', 'Permission granted successfully.');
    }

    public function destroy($id)
    {
        \App\Models\Permission::findOrFail($id)->delete();
        return back()->with('success', 'Permission removed.');
    }
}
