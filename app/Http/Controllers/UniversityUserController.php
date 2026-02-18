<?php

namespace App\Http\Controllers;

use App\Models\UniversityUser;
use App\Models\DeviceUser;
use Illuminate\Http\Request;

class UniversityUserController extends Controller
{
    public function index(Request $request)
    {
        $query = UniversityUser::with('deviceUser');

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('user_sid', 'like', '%' . $search . '%');
            });
        }

        // Status filter
        if ($request->has('status') && $request->status != '') {
            if ($request->status === 'assigned') {
                $query->whereNotNull('device_user_id');
            } elseif ($request->status === 'unassigned') {
                $query->whereNull('device_user_id');
            }
        }

        $users = $query->paginate(15)->appends($request->query());
        return view('university_users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_sid' => 'required|string|unique:university_users,user_sid',
        ]);

        UniversityUser::create($validated);

        return redirect()->route('university-users.index')->with('success', 'University user created successfully.');
    }

    public function assign(Request $request, UniversityUser $universityUser)
    {
        $validated = $request->validate([
            'device_user_id' => 'required|integer',
        ]);

        // Check if device user exists
        $deviceUser = DeviceUser::find($validated['device_user_id']);

        if (!$deviceUser) {
            return response()->json([
                'success' => false,
                'message' => 'Device user with ID ' . $validated['device_user_id'] . ' not found. Please check the ID and try again.'
            ], 404);
        }

        $universityUser->update([
            'device_user_id' => $validated['device_user_id']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully assigned to device user: ' . $deviceUser->name
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'users' => 'required|array',
            'users.*.name' => 'required|string|max:255',
            'users.*.user_sid' => 'required|string',
            'users.*.device_user_id' => 'nullable|integer',
        ]);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($validated['users'] as $userData) {
            // Check if user_sid already exists
            if (UniversityUser::where('user_sid', $userData['user_sid'])->exists()) {
                $skipped++;
                continue;
            }

            // If device_user_id is provided, validate it exists
            if (!empty($userData['device_user_id'])) {
                $deviceUser = DeviceUser::find($userData['device_user_id']);
                if (!$deviceUser) {
                    $errors[] = "Device user ID {$userData['device_user_id']} not found for {$userData['name']}";
                    $skipped++;
                    continue;
                }
            }

            UniversityUser::create([
                'name' => $userData['name'],
                'user_sid' => $userData['user_sid'],
                'device_user_id' => $userData['device_user_id'] ?? null,
            ]);

            $imported++;
        }

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
    }

    public function destroy(UniversityUser $universityUser)
    {
        $universityUser->delete();
        return redirect()->route('university-users.index')->with('success', 'University user deleted successfully.');
    }
}
