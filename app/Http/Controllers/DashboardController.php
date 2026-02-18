<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\DeviceUser;
use App\Models\AttendanceLog;

class DashboardController extends Controller
{
    public function index()
    {
        $totalDevices = Device::count();
        $totalUsers = DeviceUser::distinct('user_id_on_device')->count(); // Approximation across devices
        $activeDevices = Device::where('status', true)->count();

        // Today's check-ins count
        $todayCheckIns = AttendanceLog::whereDate('timestamp', today())->count();

        // Yesterday's check-ins for comparison
        $yesterdayCheckIns = AttendanceLog::whereDate('timestamp', today()->subDay())->count();

        // Calculate percentage change
        $percentageChange = 0;
        if ($yesterdayCheckIns > 0) {
            $percentageChange = round((($todayCheckIns - $yesterdayCheckIns) / $yesterdayCheckIns) * 100);
        }

        $recentLogs = AttendanceLog::with('device')->latest()->take(5)->get();

        return view('dashboard', compact('totalDevices', 'totalUsers', 'activeDevices', 'todayCheckIns', 'yesterdayCheckIns', 'percentageChange', 'recentLogs'));
    }
}
