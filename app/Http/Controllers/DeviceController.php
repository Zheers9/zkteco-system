<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceUser;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Fsuuaas\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DeviceController extends Controller
{
    private function getZkInstance(Device $device, $timeoutSec = 5)
    {
        $zk = new ZKTeco($device->ip, $device->port);
        // Comm Key will be handled during connection

        // Ensure sockets extension is loaded for reliable timeout handling
        if (function_exists('socket_set_option')) {
            $timeout = array('sec' => $timeoutSec, 'usec' => 0);
            // We need to access the protected property _zkclient if possible, or trust the library defaults if not accessible directly.
            // The original code accessed a public property or magic property. 
            // If _zkclient is not accessible, this line might fail. 
            // Assuming the library exposes it or it's public based on previous code usage.

            // Wrap in try-catch just in case the property is not initialized yet or accessible
            try {
                if (isset($zk->_zkclient)) {
                    socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, $timeout);
                    socket_set_option($zk->_zkclient, SOL_SOCKET, SO_SNDTIMEO, $timeout);
                }
            } catch (\Exception $e) {
                Log::warning("Could not set socket options: " . $e->getMessage());
            }
        }

        return $zk;
    }

    private function connectWithAuth(ZKTeco $zk)
    {
        if (!$zk->connect()) {
            return false;
        }

        // Manual Auth for UDP using CMD_AUTH (1102) and Comm Key (9625)
        // Protocol: Command = 1102, Command String = CommKey packed as 4-byte int (Little Endian)
        $CMD_AUTH = 1102;
        $command_string = pack('V', 9625);

        $zk->_command($CMD_AUTH, $command_string);
        // We assume auth succeeds if connect succeeded, or subsequent commands will fail.

        return true;
    }

    public function index()
    {
        $devices = Device::latest()->get();
        return view('devices.index', compact('devices'));
    }

    public function create()
    {
        return view('devices.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip' => 'required|ipv4|unique:devices,ip',
            'port' => 'required|integer',
            'location' => 'nullable|string',
        ]);

        Device::create($validated);

        return redirect()->route('devices.index')->with('success', 'Device added successfully.');
    }

    public function show(Device $device)
    {
        // Overview Stats
        $usersCount = $device->users()->count();
        $logsCount = $device->logs()->count();
        $recentLogs = $device->logs()->latest()->take(5)->get();

        return view('devices.show', compact('device', 'usersCount', 'logsCount', 'recentLogs'));
    }

    public function users(Device $device)
    {
        $users = $device->users()->paginate(15);
        return view('devices.users', compact('device', 'users'));
    }

    public function attendance(Device $device)
    {
        $logs = $device->logs()->with('deviceUser')->orderBy('timestamp', 'desc')->paginate(50);
        return view('devices.attendance', compact('device', 'logs'));
    }

    // -----------------------------------------------------------------------
    // SYNC ALL DEVICES
    // -----------------------------------------------------------------------

    public function syncAllPage()
    {
        $devices = Device::all();
        return view('devices.sync-all', compact('devices'));
    }

    /**
     * Ping all devices quickly (2s timeout each) and return their status.
     * Called first so the UI can show which devices are reachable before syncing.
     */
    public function pingAll(Request $request)
    {
        $devices = Device::all();
        $results = [];

        foreach ($devices as $device) {
            $zk = new ZKTeco($device->ip, $device->port);
            try {
                $connected = @$zk->connect(); // suppress warnings
                if ($connected) {
                    $zk->disconnect();
                    $device->update(['status' => true, 'last_connected_at' => now()]);
                    $results[$device->id] = ['online' => true, 'message' => 'Online'];
                } else {
                    $device->update(['status' => false]);
                    $results[$device->id] = ['online' => false, 'message' => 'Unreachable'];
                }
            } catch (\Exception $e) {
                $device->update(['status' => false]);
                $results[$device->id] = ['online' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Dispatch background sync jobs for all (or selected) devices simultaneously.
     * Each device gets its own job — they run in parallel via the queue.
     */
    public function dispatchAll(Request $request)
    {
        $deviceIds = $request->input('device_ids', []); // array of IDs to sync
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $devices = Device::when(!empty($deviceIds), fn($q) => $q->whereIn('id', $deviceIds))->get();

        $dispatched = [];
        foreach ($devices as $device) {
            // Reset progress cache so the UI starts fresh
            $cacheKey = "device_sync_progress_{$device->id}";
            Cache::put($cacheKey, [
                'status' => 'queued',
                'progress' => 0,
                'message' => 'Queued...',
            ], 600);

            \App\Jobs\SyncDeviceAttendanceJob::dispatch($device, [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            $dispatched[] = $device->id;
        }

        return response()->json([
            'success' => true,
            'dispatched' => $dispatched,
            'message' => count($dispatched) . ' sync jobs dispatched.',
        ]);
    }


    public function allUsers(Request $request)
    {
        $query = DeviceUser::with('device');

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('user_id_on_device', 'like', '%' . $search . '%');
            });
        }

        $users = $query->paginate(15)->appends($request->query());
        return view('device_users.index', compact('users'));
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Device deleted.');
    }

    public function testConnection(Device $device)
    {
        $zk = $this->getZkInstance($device, 2); // 2 second timeout for ping
        if ($this->connectWithAuth($zk)) {
            $device->update(['last_connected_at' => now(), 'status' => true]);
            $zk->disconnect();
            return response()->json(['success' => true, 'message' => 'Connection successful!']);
        }

        $device->update(['status' => false]);
        return response()->json(['success' => false, 'message' => 'Could not connect to device.']);
    }

    public function syncUsers(Device $device)
    {
        $zk = $this->getZkInstance($device, 5); // 5s timeout

        if (!$this->connectWithAuth($zk)) {
            return response()->json(['success' => false, 'message' => 'Connection failed.']);
        }

        try {
            $users = $zk->getUser();
            $count = 0;
            foreach ($users as $u) {
                // $u usually has: uid, userid, name, role, password, cardno
                // We map 'userid' (from device) to 'user_id_on_device' in DB.

                DeviceUser::updateOrCreate(
                    [
                        'device_id' => $device->id,
                        'user_id_on_device' => $u['userid']
                    ],
                    [
                        'name' => $u['name'],
                        'role' => $u['role'],
                        'password' => $u['password'],
                        'card_number' => $u['cardno']
                    ]
                );
                $count++;
            }
            $zk->disconnect();

            $device->update(['last_connected_at' => now(), 'status' => true]);

            return response()->json(['success' => true, 'message' => "Synced $count users."]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function syncAttendance(Request $request, Device $device)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if ($request->has('async')) {
            $cacheKey = "device_sync_progress_{$device->id}";
            Cache::put($cacheKey, [
                'status' => 'queued',
                'progress' => 0,
                'message' => 'Sync request queued...'
            ], 600);

            \App\Jobs\SyncDeviceAttendanceJob::dispatch($device, $request->only('start_date', 'end_date'));

            return response()->json([
                'success' => true,
                'message' => 'Sync request queued in background.',
                'status' => 'queued'
            ]);
        }

        // --- Synchronous path ---
        $zk = $this->getZkInstance($device, 30);

        if (!$this->connectWithAuth($zk)) {
            return response()->json(['success' => false, 'message' => 'Connection failed.']);
        }

        try {
            $zk->enableDevice();
            $logs = $zk->getAttendance();
            $totalFromDevice = count($logs);

            Log::info("[Sync Sync] Downloaded {$totalFromDevice} records from {$device->name}");

            // ---------------------------------------------------------------
            // SMART FILTER: Use last_synced_at as the primary cutoff.
            // Skip all records we have already seen on previous syncs.
            // ---------------------------------------------------------------
            $lastSyncedAt = $device->last_synced_at
                ? $device->last_synced_at->format('Y-m-d H:i:s')
                : null;

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Effective cutoff = later of last_synced_at and start_date
            $effectiveCutoff = null;
            if ($lastSyncedAt && $startDate) {
                $effectiveCutoff = max($lastSyncedAt, $startDate . ' 00:00:00');
            } elseif ($lastSyncedAt) {
                $effectiveCutoff = $lastSyncedAt;
            } elseif ($startDate) {
                $effectiveCutoff = $startDate . ' 00:00:00';
            }

            $effectiveEnd = $endDate ? $endDate . ' 23:59:59' : null;
            $newestTimestamp = $lastSyncedAt;

            // Fast in-memory filter — zero DB queries here
            $filteredLogs = [];
            foreach ($logs as $log) {
                $ts = $log['timestamp'];
                if ($effectiveCutoff && $ts <= $effectiveCutoff)
                    continue;
                if ($effectiveEnd && $ts > $effectiveEnd)
                    continue;
                $filteredLogs[] = $log;
                if ($newestTimestamp === null || $ts > $newestTimestamp) {
                    $newestTimestamp = $ts;
                }
            }

            $filteredCount = count($filteredLogs);
            $skipped = $totalFromDevice - $filteredCount;

            if ($filteredCount === 0) {
                $zk->disableDevice();
                $zk->disconnect();
                $device->update(['last_connected_at' => now(), 'status' => true, 'last_synced_at' => now()]);
                return response()->json([
                    'success' => true,
                    'message' => "All records already synced. {$skipped} old records skipped."
                ]);
            }

            // One DB query to get existing keys in the relevant range
            $minTs = $effectiveCutoff;
            $existingKeys = AttendanceLog::where('device_id', $device->id)
                ->when($minTs, fn($q) => $q->where('timestamp', '>', $minTs))
                ->when($effectiveEnd, fn($q) => $q->where('timestamp', '<=', $effectiveEnd))
                ->get(['user_id_on_device', 'timestamp'])
                ->mapWithKeys(function ($log) {
                    return [$log->user_id_on_device . '|' . $log->timestamp->format('Y-m-d H:i:s') => true];
                });

            // Bulk insert in chunks
            $toInsert = [];
            $insertedCount = 0;
            $now = now();

            foreach ($filteredLogs as $log) {
                $key = $log['id'] . '|' . $log['timestamp'];
                if (isset($existingKeys[$key]))
                    continue;

                $toInsert[] = [
                    'device_id' => $device->id,
                    'user_id_on_device' => $log['id'],
                    'timestamp' => $log['timestamp'],
                    'uid' => $log['uid'],
                    'status' => $log['state'],
                    'type' => $log['type'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($toInsert) >= 1000) {
                    AttendanceLog::insert($toInsert);
                    $insertedCount += count($toInsert);
                    $toInsert = [];
                }
            }

            if (!empty($toInsert)) {
                AttendanceLog::insert($toInsert);
                $insertedCount += count($toInsert);
            }

            // Save last_synced_at so next sync skips all these records instantly
            $device->update([
                'last_connected_at' => now(),
                'status' => true,
                'last_synced_at' => $newestTimestamp ?? now(),
            ]);

            $zk->disableDevice();
            $zk->disconnect();

            return response()->json([
                'success' => true,
                'message' => "{$insertedCount} new records saved. {$skipped} old records skipped instantly. Next sync will be much faster!"
            ]);

        } catch (\Exception $e) {
            Log::error("[Sync] Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }



    public function syncProgress(Device $device)
    {
        $key = "device_sync_progress_{$device->id}";
        $data = Cache::get($key, [
            'status' => 'unknown',
            'progress' => 0,
            'message' => 'No sync in progress'
        ]);
        return response()->json($data);
    }

    // Transfer Data Methods
    public function transferIndex()
    {
        $devices = Device::all();
        return view('devices.transfer', compact('devices'));
    }

    public function fetchDeviceUsers(Device $device)
    {
        $zk = $this->getZkInstance($device, 5);
        if (!$this->connectWithAuth($zk)) {
            return response()->json(['success' => false, 'message' => 'Could not connect to device.']);
        }

        try {
            $zk->enableDevice();
            $users = $zk->getUser();
            Log::info("Fetch Users Response Type: " . gettype($users));

            if (!is_array($users)) {
                $users = [];
            }

            // Normalize array - ZK lib returns associative array (keys are IDs), we need indexed array for JSON
            $users = array_values($users);

            $zk->disableDevice();
            $zk->disconnect();

            return response()->json(['success' => true, 'users' => $users]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Device Error: ' . $e->getMessage()
            ], 500);
        } catch (\Error $e) {
            return response()->json([
                'success' => false,
                'message' => 'System Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function transfer(Request $request)
    {
        set_time_limit(300); // Allow up to 5 minutes for transfer
        $request->validate([
            'source_device_id' => 'required|exists:devices,id',
            'target_device_ids' => 'required|array',
            'target_device_ids.*' => 'exists:devices,id',
            'user_ids' => 'required|array', // User IDs (string/int) to transfer
        ]);

        $sourceDevice = Device::find($request->source_device_id);
        $targetDevices = Device::findMany($request->target_device_ids);
        $userIdsToTransfer = $request->user_ids;

        // 1. Fetch Data from Source
        // 1. Fetch Data from Source
        $zkSource = $this->getZkInstance($sourceDevice, 5);
        if (!$this->connectWithAuth($zkSource)) {
            return response()->json(['success' => false, 'message' => "Could not connect to Source Device: {$sourceDevice->name}"]);
        }

        $usersToTransfer = [];
        try {
            $zkSource->enableDevice();
            $allUsers = $zkSource->getUser();

            if (!is_array($allUsers)) {
                $allUsers = [];
            } else {
                // Ensure array_values to fix indexing issues
                $allUsers = array_values($allUsers);
            }

            foreach ($allUsers as $u) {
                // $u is array: [userid, name, cardno, uid, role, password]
                // We filter by 'userid' (the displayed id)
                if (in_array((string) $u['userid'], $userIdsToTransfer)) {
                    // Fetch Fingerprints
                    // Note: UID (internal index) is needed for getFingerprint
                    $fingerprints = $zkSource->getFingerprint($u['uid']);
                    $u['fingerprints'] = $fingerprints;
                    $usersToTransfer[] = $u;
                }
            }
            $zkSource->disableDevice();
            $zkSource->disconnect();

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => "Error reading source: " . $e->getMessage()]);
        }

        if (empty($usersToTransfer)) {
            return response()->json(['success' => false, 'message' => 'No users found to transfer.']);
        }

        // 2. Push Data to Targets
        $results = [];
        $successCount = 0;

        foreach ($targetDevices as $target) {
            $zkTarget = $this->getZkInstance($target, 5);
            if (!$this->connectWithAuth($zkTarget)) {
                $results[$target->name] = 'Failed to connect';
                continue;
            }

            try {
                $zkTarget->enableDevice();

                // 1. Fetch existing users from Target to handle UIDs
                $targetUsers = $zkTarget->getUser();
                $targetUserMap = []; // userid -> uid
                $usedUids = [];

                if (is_array($targetUsers)) {
                    foreach ($targetUsers as $tu) {
                        $targetUserMap[$tu['userid']] = $tu['uid'];
                        $usedUids[] = $tu['uid'];
                    }
                }

                $nextUid = empty($usedUids) ? 1 : (max($usedUids) + 1);

                foreach ($usersToTransfer as $u) {
                    $targetUid = 0;
                    $isNew = false;

                    // Determine UID
                    if (isset($targetUserMap[$u['userid']])) {
                        // User exists on target, update them using THEIR uid
                        $targetUid = $targetUserMap[$u['userid']];
                    } else {
                        // User does not exist, create new UID
                        // Ensure nextUid is not in usedUids (safety check)
                        while (in_array($nextUid, $usedUids)) {
                            $nextUid++;
                        }
                        $targetUid = $nextUid;
                        $usedUids[] = $nextUid; // Mark as used
                        $isNew = true;
                    }

                    // Set User
                    $uName = $u['name'];
                    $uPassword = $u['password'] ?? '';
                    $uRole = (int) ($u['role'] ?? 0);
                    $uCard = $u['cardno'] ?? 0;

                    Log::info("Transferring to {$target->name}: UID=$targetUid, UserID={$u['userid']}, Name=$uName");

                    // setUser($uid, $userid, $name, $password, $role, $cardno)
                    $setUserResult = $zkTarget->setUser(
                        (int) $targetUid,
                        (string) $u['userid'],
                        (string) $uName,
                        (string) $uPassword,
                        (int) $uRole,
                        (string) $uCard
                    );

                    if (!$setUserResult) {
                        Log::error("Failed to set user on {$target->name}: " . json_encode($u));
                        throw new \Exception("Failed to write User ID {$u['userid']} to device.");
                    }

                    // Set Fingerprints using the TARGET UID
                    if (!empty($u['fingerprints'])) {
                        Log::info("Transferring fingerprints for UID=$targetUid count=" . count($u['fingerprints']));
                        // setFingerprint expects an array of [finger_id => template_data]
                        // $u['fingerprints'] is already in that format from getFingerprint
                        $zkTarget->setFingerprint($targetUid, $u['fingerprints']);
                    }
                }

                $zkTarget->disableDevice();
                $zkTarget->disconnect();
                $results[$target->name] = 'Success';
                $successCount++;

            } catch (\Exception $e) {
                Log::error("Transfer Error on {$target->name}: " . $e->getMessage());
                $results[$target->name] = 'Error: ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer process completed.',
            'results' => $results,
            'transferred_count' => count($usersToTransfer)
        ]);
    }
}
