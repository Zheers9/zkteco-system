<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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

    protected $device;
    protected $params;

    /**
     * Create a new job instance.
     */
    public function __construct(Device $device, array $params = [])
    {
        $this->device = $device;
        $this->params = $params;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $device = $this->device;
        $cacheKey = "device_sync_progress_{$device->id}";

        // Initialize progress
        Cache::put($cacheKey, [
            'status' => 'connecting',
            'progress' => 0,
            'total' => 0,
            'processed' => 0,
            'new' => 0,
            'message' => 'Connecting to device...'
        ], 300); // 5 minutes TTL

        Log::info("Starting background sync for device: {$device->name}");

        $zk = new ZKTeco($device->ip, $device->port);
        // Timeout configuration
        $timeout = array('sec' => 30, 'usec' => 0); // Longer timeout for background job
        socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, $timeout);
        socket_set_option($zk->_zkclient, SOL_SOCKET, SO_SNDTIMEO, $timeout);

        if (!$zk->connect()) {
            Log::error("Background Sync: Failed to connect to {$device->name}");
            Cache::put($cacheKey, [
                'status' => 'failed',
                'message' => "Failed to connect to device {$device->name}"
            ], 300);
            return;
        }

        try {
            Cache::put($cacheKey, [
                'status' => 'fetching',
                'message' => 'Fetching attendance logs...'
            ], 300);

            $zk->enableDevice();
            $logs = $zk->getAttendance();

            Log::info("Background Sync: Fetched " . count($logs) . " logs from {$device->name}");

            $totalLogs = count($logs);
            $processed = 0;
            $newCount = 0;

            // Update cache with total
            Cache::put($cacheKey, [
                'status' => 'processing',
                'progress' => 0,
                'total' => $totalLogs,
                'processed' => 0,
                'new' => 0,
                'message' => "Processing $totalLogs records..."
            ], 300);

            $startDate = $this->params['start_date'] ?? null;
            $endDate = $this->params['end_date'] ?? null;

            foreach ($logs as $index => $log) {
                // Date Filter (Performance optimization for DB writes)
                if ($startDate && $log['timestamp'] < $startDate . ' 00:00:00') {
                    $processed++;
                    continue;
                }
                if ($endDate && $log['timestamp'] > $endDate . ' 23:59:59') {
                    $processed++;
                    continue;
                }

                $attendanceLog = AttendanceLog::firstOrCreate(
                    [
                        'device_id' => $device->id,
                        'user_id_on_device' => $log['id'],
                        'timestamp' => $log['timestamp']
                    ],
                    [
                        'uid' => $log['uid'],
                        'status' => $log['state'], // Check in/out
                        'type' => $log['type']
                    ]
                );

                if ($attendanceLog->wasRecentlyCreated) {
                    $newCount++;
                }

                $processed++;

                // Update progress every 50 records or last record
                if ($processed % 50 === 0 || $processed === $totalLogs) {
                    $percentage = $totalLogs > 0 ? round(($processed / $totalLogs) * 100) : 100;
                    Cache::put($cacheKey, [
                        'status' => 'processing',
                        'progress' => $percentage,
                        'total' => $totalLogs,
                        'processed' => $processed,
                        'new' => $newCount,
                        'message' => "Processing: $percentage% ($processed/$totalLogs)"
                    ], 300);
                }
            }

            $zk->disableDevice();
            $zk->disconnect();

            $device->update(['last_connected_at' => now(), 'status' => true]);
            Log::info("Background Sync: Completed for {$device->name}");

            Cache::put($cacheKey, [
                'status' => 'completed',
                'progress' => 100,
                'total' => $totalLogs,
                'processed' => $processed,
                'new' => $newCount,
                'message' => "Sync Completed. Used $processed records. Saved $newCount new records."
            ], 300);

        } catch (\Exception $e) {
            Log::error("Background Sync Error: " . $e->getMessage());
            Cache::put($cacheKey, [
                'status' => 'failed',
                'message' => "Error: " . $e->getMessage()
            ], 300);
        }
    }
}
