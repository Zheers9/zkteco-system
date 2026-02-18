<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Device;
use App\Models\AttendanceLog;
use Carbon\Carbon;

class AdmsController extends Controller
{
    /**
     * Handle Connection/Heartbeat
     */
    public function getrequest(Request $request)
    {
        $sn = $request->input('SN');
        Log::info("ADMS Heartbeat from: $sn");

        // Here we could return commands if we had any queued
        // Format: C:<id>:<command string>

        return "OK";
    }

    /**
     * Handle Data Push (Attendance Logs, Users, etc)
     */
    public function cdata(Request $request)
    {
        // Often sent as GET parameters for handshake, and POST body for data
        $sn = $request->input('SN');
        $table = $request->input('table');

        Log::info("ADMS Data Received from $sn - Table: $table");

        if (!$sn) {
            return "ERROR: No SN provided";
        }

        // Verify Device
        // Ideally we match by SN, but for now we fallback to first device found or create generic logic
        $device = Device::where('name', $sn)->first() ?? Device::first();

        if ($table == 'ATTLOG') {
            // Processing Attendance Logs
            $content = $request->getContent();

            // Log first 200 chars to debug format
            Log::info("ADMS Log Content Start: " . substr($content, 0, 200));

            // Parse Lines (ADMS sends multiple lines in one request)
            $lines = explode("\n", $content);
            $count = 0;

            foreach ($lines as $line) {
                if (empty(trim($line)))
                    continue;

                // Typical Format: USERID \t CHECKTIME \t CHECKTYPE \t VERIFYCODE
                // We split by tab
                $parts = explode("\t", trim($line));

                if (count($parts) >= 2) {
                    $userId = $parts[0];
                    $timestamp = $parts[1];
                    $status = $parts[2] ?? 0;
                    $type = $parts[3] ?? 0;

                    if ($device) {
                        // Check for duplicate before enabling creation
                        $exists = AttendanceLog::where('device_id', $device->id)
                            ->where('user_id_on_device', $userId)
                            ->where('timestamp', $timestamp)
                            ->exists();

                        if (!$exists) {
                            AttendanceLog::create([
                                'device_id' => $device->id,
                                'user_id_on_device' => $userId,
                                'timestamp' => $timestamp,
                                'status' => $status,
                                'type' => $type
                            ]);
                            $count++;
                        }
                    }
                }
            }

            Log::info("ADMS Imported $count new logs from $sn");
            return "OK";
        }

        return "OK";
    }

    /**
     * Handle Device Commands
     */
    public function devicecmd(Request $request)
    {
        Log::info("ADMS Command Response: " . json_encode($request->all()));
        return "OK";
    }
}
