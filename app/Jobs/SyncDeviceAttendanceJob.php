<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Device;
use App\Models\AttendanceLog;
use Fsuuaas\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SyncDeviceAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // Allow up to 10 minutes for large syncs
    public $tries = 1;     // Don't retry on failure - device state may change

    protected $device;
    protected $params;

    public function __construct(Device $device, array $params = [])
    {
        $this->device = $device;
        $this->params = $params;
    }

    public function handle(): void
    {
        ini_set('memory_limit', '512M');

        $device = $this->device;
        $cacheKey = "device_sync_progress_{$device->id}";

        $this->updateProgress($cacheKey, 'connecting', 0, 'Connecting to device...');
        Log::info("[Sync] Starting for device: {$device->name} ({$device->ip})");

        // --- Connect ---
        $zk = new ZKTeco($device->ip, $device->port);
        if (function_exists('socket_set_option') && isset($zk->_zkclient)) {
            try {
                $timeout = ['sec' => 30, 'usec' => 0];
                socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, $timeout);
                socket_set_option($zk->_zkclient, SOL_SOCKET, SO_SNDTIMEO, $timeout);
            } catch (\Exception $e) {
                Log::warning("[Sync] socket_set_option failed: " . $e->getMessage());
            }
        }

        if (!$zk->connect()) {
            Log::error("[Sync] Connection failed for {$device->name}");
            $this->updateProgress($cacheKey, 'failed', 0, "Failed to connect to device. Check IP and network.");
            return;
        }

        try {
            $this->updateProgress($cacheKey, 'fetching', 5, 'Connected! Downloading attendance logs from device...');

            $zk->enableDevice();
            $logs = $zk->getAttendance();
            $totalFromDevice = count($logs);

            Log::info("[Sync] Downloaded {$totalFromDevice} raw records from {$device->name}");

            if ($totalFromDevice === 0) {
                $zk->disableDevice();
                $zk->disconnect();
                $device->update(['last_connected_at' => now(), 'status' => true, 'last_synced_at' => now()]);
                $this->updateProgress($cacheKey, 'completed', 100, 'Device has no attendance records.', 0);
                return;
            }

            $this->updateProgress($cacheKey, 'processing', 20, "Downloaded {$totalFromDevice} records. Filtering...");

            // ---------------------------------------------------------------
            // SMART FILTER: Use last_synced_at as the primary cutoff.
            // This is the KEY optimization: on 2nd+ sync, we skip all old records
            // instantly in memory without any DB queries per record.
            // ---------------------------------------------------------------
            $lastSyncedAt = $device->last_synced_at
                ? $device->last_synced_at->format('Y-m-d H:i:s')
                : null;

            // Also respect manual date range filters from the request
            $startDate = $this->params['start_date'] ?? null;
            $endDate = $this->params['end_date'] ?? null;

            // The effective cutoff is the LATER of last_synced_at and start_date
            $effectiveCutoff = null;
            if ($lastSyncedAt && $startDate) {
                $effectiveCutoff = max($lastSyncedAt, $startDate . ' 00:00:00');
            } elseif ($lastSyncedAt) {
                $effectiveCutoff = $lastSyncedAt;
            } elseif ($startDate) {
                $effectiveCutoff = $startDate . ' 00:00:00';
            }

            $effectiveEnd = $endDate ? $endDate . ' 23:59:59' : null;

            Log::info("[Sync] Effective cutoff: " . ($effectiveCutoff ?? 'none') . " | End: " . ($effectiveEnd ?? 'none'));

            // Fast in-memory filter - no DB queries here
            $filteredLogs = [];
            $newestTimestamp = $lastSyncedAt; // Track the newest record we see

            foreach ($logs as $log) {
                $ts = $log['timestamp'];

                // Skip records older than our cutoff
                if ($effectiveCutoff && $ts <= $effectiveCutoff) {
                    continue;
                }

                // Skip records newer than end date
                if ($effectiveEnd && $ts > $effectiveEnd) {
                    continue;
                }

                $filteredLogs[] = $log;

                // Track newest timestamp for updating last_synced_at
                if ($newestTimestamp === null || $ts > $newestTimestamp) {
                    $newestTimestamp = $ts;
                }
            }

            $filteredCount = count($filteredLogs);
            Log::info("[Sync] After filtering: {$filteredCount} new records to process (skipped " . ($totalFromDevice - $filteredCount) . ")");

            $this->updateProgress($cacheKey, 'processing', 40, "Filtered to {$filteredCount} new records. Checking database...");

            if ($filteredCount === 0) {
                $zk->disableDevice();
                $zk->disconnect();
                $device->update(['last_connected_at' => now(), 'status' => true, 'last_synced_at' => now()]);
                $this->updateProgress($cacheKey, 'completed', 100, "All records already synced. Device is up to date!", 0);
                return;
            }

            // ---------------------------------------------------------------
            // DEDUPLICATION: One single DB query to get existing keys in range
            // instead of N queries (one per record).
            // ---------------------------------------------------------------
            $minTs = $effectiveCutoff ?? ($filteredLogs[0]['timestamp'] ?? null);
            $existingKeys = AttendanceLog::where('device_id', $device->id)
                ->when($minTs, fn($q) => $q->where('timestamp', '>', $minTs))
                ->when($effectiveEnd, fn($q) => $q->where('timestamp', '<=', $effectiveEnd))
                ->get(['user_id_on_device', 'timestamp'])
                ->mapWithKeys(function ($log) {
                    // Key format: "<user_id_on_device>|<timestamp>" — must match exactly below
                    return [$log->user_id_on_device . '|' . $log->timestamp->format('Y-m-d H:i:s') => true];
                });

            $this->updateProgress($cacheKey, 'processing', 60, "Inserting new records into database...");

            // ---------------------------------------------------------------
            // BULK INSERT in chunks of 500
            // ---------------------------------------------------------------
            $toInsert = [];
            $insertedCount = 0;
            $now = now();
            $chunkSize = 500;

            foreach ($filteredLogs as $log) {
                // $log['id'] from the device = user_id_on_device in the DB
                // Key must match exactly what we built from the DB above
                $key = $log['id'] . '|' . date('Y-m-d H:i:s', strtotime($log['timestamp']));

                if (isset($existingKeys[$key])) {
                    continue; // Already in DB
                }

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

                if (count($toInsert) >= $chunkSize) {
                    AttendanceLog::insert($toInsert);
                    $insertedCount += count($toInsert);
                    $toInsert = [];

                    $pct = 60 + (int) (($insertedCount / $filteredCount) * 35);
                    $this->updateProgress($cacheKey, 'processing', min($pct, 95), "Saving... {$insertedCount}/{$filteredCount}");
                }
            }

            // Insert remaining
            if (!empty($toInsert)) {
                AttendanceLog::insert($toInsert);
                $insertedCount += count($toInsert);
            }

            // ---------------------------------------------------------------
            // Update device: mark last_synced_at to the newest record we saw.
            // Next sync will use this as the cutoff and skip all older records.
            // ---------------------------------------------------------------
            $device->update([
                'last_connected_at' => now(),
                'status' => true,
                'last_synced_at' => $newestTimestamp ?? now(),
            ]);

            $zk->disableDevice();
            $zk->disconnect();

            Log::info("[Sync] Completed for {$device->name}. Inserted: {$insertedCount}. Last sync: {$newestTimestamp}");

            $skipped = $totalFromDevice - $filteredCount;
            $msg = "✅ Done! {$insertedCount} new records saved. {$skipped} old records skipped instantly. Next sync will be even faster!";
            $this->updateProgress($cacheKey, 'completed', 100, $msg, $insertedCount);

        } catch (\Exception $e) {
            Log::error("[Sync] Error for {$device->name}: " . $e->getMessage());
            $this->updateProgress($cacheKey, 'failed', 0, "Error: " . $e->getMessage());
        }
    }

    private function updateProgress(string $key, string $status, int $progress, string $message, ?int $new = null): void
    {
        $data = [
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
        ];
        if ($new !== null) {
            $data['new'] = $new;
        }
        Cache::put($key, $data, 600);
    }
}
