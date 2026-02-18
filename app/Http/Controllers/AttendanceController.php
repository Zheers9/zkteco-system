<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Exports\AttendanceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceLog::query();

        // Default to last month if not specified (First load)
        if (!$request->has('start_date')) {
            $request->merge(['start_date' => date('Y-m-d', strtotime('-1 month'))]);
        }
        if (!$request->has('end_date')) {
            $request->merge(['end_date' => date('Y-m-d')]);
        }

        if ($request->start_date) {
            $query->whereDate('timestamp', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('timestamp', '<=', $request->end_date);
        }

        $logs = $query->orderBy('timestamp', 'desc')->paginate(15);
        $logs->appends($request->all());

        $devices = \App\Models\Device::all();

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return view('attendance.index', compact('logs', 'devices'));
    }

    public function report(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));

        // Eager load device to reduce N+1
        $logs = AttendanceLog::with('device')
            ->whereBetween('timestamp', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('timestamp') // Ensure ordered by time
            ->get();

        // Group by User ID (assuming unique across devices)
        // Linking to user name via DeviceUser table
        $usersQuery = \App\Models\DeviceUser::query();

        if ($request->filled('search')) {
            $usersQuery->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter out numeric names
        $users = $usersQuery->pluck('name', 'user_id_on_device')
            ->filter(function ($name) {
                return !is_numeric($name);
            });

        // Filter logs only for found users
        $groupedLogs = $logs->groupBy('user_id_on_device')->intersectByKeys($users);

        $schedules = \App\Models\Schedule::all()->keyBy('day_of_week');

        // 6. Fetch Permissions for this range
        $permissions = \App\Models\Permission::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('user_id_on_device');

        $report = [];

        foreach ($groupedLogs as $userId => $userLogs) {
            // Group user logs by Date
            $logsByDate = $userLogs->groupBy(function ($log) {
                return \Carbon\Carbon::parse($log->timestamp)->format('Y-m-d');
            });

            // Iterate through every day in range, or just logged days?
            // The current report only shows days with logs. 
            // Permissions might exist on days WITHOUT logs. 
            // If the user wants to see "Permission" on a day they didn't show up, we need to iterate dates.
            // But the current controller structure iterates $logsByDate.
            // If the user is Absent (no logs), they won't appear in $groupedLogs loop for that day unless we fill gaps.
            // However, the requested logic was "if have 1 data checknot checout make it abcent... mark as permission"
            // This implies overriding the "Absent" status derived from LOGS.

            // For now, let's just override the ones that result in "Absent" due to invalid punches.

            $userPermissions = $permissions[$userId] ?? collect();

            foreach ($logsByDate as $date => $dayLogs) {
                $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek; // 0 (Sun) - 6 (Sat)
                $schedule = $schedules[$dayOfWeek] ?? null;

                $firstPunch = $dayLogs->first();
                $lastPunch = $dayLogs->last();

                $checkIn = $firstPunch;
                $checkOut = ($dayLogs->count() > 1) ? $lastPunch : null;

                $status = 'Present';
                $late = false;
                $earlyLeave = false;
                $hasPermission = $userPermissions->contains('date', \Carbon\Carbon::parse($date));

                // Compare with Schedule
                if ($dayLogs->count() < 2) {
                    $status = $hasPermission ? 'Permission' : 'Absent';
                } elseif ($checkOut && \Carbon\Carbon::parse($checkIn->timestamp)->format('H:i') === \Carbon\Carbon::parse($checkOut->timestamp)->format('H:i')) {
                    $status = $hasPermission ? 'Permission' : 'Absent';
                } elseif ($schedule && !$schedule->is_off_day) {
                    $scheduledStart = \Carbon\Carbon::parse($date . ' ' . $schedule->start_time);
                    $scheduledEnd = \Carbon\Carbon::parse($date . ' ' . $schedule->end_time);

                    if ($checkIn && \Carbon\Carbon::parse($checkIn->timestamp)->gt($scheduledStart->addMinutes(15))) {
                        $late = true;
                        $status = 'Late';
                    }
                    if ($checkOut && \Carbon\Carbon::parse($checkOut->timestamp)->lt($scheduledEnd)) {
                        $earlyLeave = true;
                        $status = ($status == 'Late') ? 'Late & Early Leave' : 'Early Leave';
                    }

                    // If marked Late/Early but has permission? Usually Permission is for full day absence.
                    // But if they have permission, maybe we ignore late?
                    // User said "if 1 day left not have data dont makr abcnet amkr as permision". 
                    // So overrides Absent.
                }

                $report[] = [
                    'user_id' => $userId,
                    'name' => $users[$userId] ?? 'Unknown (' . $userId . ')',
                    'date' => $date,
                    'day_name' => \Carbon\Carbon::parse($date)->format('l'),
                    'check_in' => $checkIn ? \Carbon\Carbon::parse($checkIn->timestamp)->format('H:i') : '-',
                    'check_out' => $checkOut ? \Carbon\Carbon::parse($checkOut->timestamp)->format('H:i') : '-',
                    'device_name' => $checkIn ? $checkIn->device->name : ($checkOut ? $checkOut->device->name : '-'),
                    'schedule_in' => $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-',
                    'schedule_out' => $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '-',
                    'status' => $status,
                ];
            }
        }

        // Sort by Date then Name
        usort($report, function ($a, $b) {
            $dateCmp = strcmp($b['date'], $a['date']); // Descending date
            if ($dateCmp === 0) {
                return strcmp($a['name'], $b['name']);
            }
            return $dateCmp;
        });

        return view('attendance.report', compact('report', 'startDate', 'endDate'));
    }

    public function export(Request $request)
    {
        $filename = 'attendance_logs_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new AttendanceExport($request), $filename);
    }

    public function analytics(Request $request)
    {
        // Default to current month
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));

        $requiredDays = \App\Models\Setting::where('key', 'required_work_days')->value('value') ?? 3;

        // Align dates to full weeks (Sat - Fri)
        // startOfWeek(Carbon::SATURDAY) means the week technically starts on Saturday.
        // If we pick Feb 1 (Sun), startOfWeek -> Jan 31 (Sat). Correct.
        $start = \Carbon\Carbon::parse($startDate)->startOfWeek(\Carbon\Carbon::SATURDAY);
        $end = \Carbon\Carbon::parse($endDate)->endOfWeek(\Carbon\Carbon::FRIDAY);

        // We fetch logs for the full extended range to allow checking accurate attendance
        $logs = AttendanceLog::whereBetween('timestamp', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')])
            ->selectRaw('DATE(timestamp) as date, user_id_on_device')
            ->groupByRaw('DATE(timestamp), user_id_on_device')
            ->havingRaw('COUNT(*) >= 2') // Treat single punch as absent (not attended)
            ->get();

        // Group logs by user
        $userAttendance = $logs->groupBy('user_id_on_device');

        // Fetch users and filter out those with numeric names
        $usersQuery = \App\Models\DeviceUser::query();

        if ($request->filled('search')) {
            $usersQuery->where('name', 'like', '%' . $request->search . '%');
        }

        $users = $usersQuery->pluck('name', 'user_id_on_device')
            ->filter(function ($name) {
                return !is_numeric($name);
            });

        $analytics = [];

        $allUserIds = $users->keys();

        foreach ($allUserIds as $userId) {
            $userLogs = $userAttendance[$userId] ?? collect();

            $current = $start->copy();
            $totalAbsences = 0;
            $weeksAnalyzed = 0;
            $totalPresentDays = 0;

            // Loop by weeks
            while ($current->lte($end)) {
                $weekStart = $current->copy();
                // weekEnd is simply 6 days later, or explicit endOfWeek(FRIDAY)
                $weekEnd = $current->copy()->endOfWeek(\Carbon\Carbon::FRIDAY);

                // Fetch permissions for this user
                $userPermissions = \App\Models\Permission::where('user_id_on_device', $userId)
                    ->whereBetween('date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                    ->get();

                // Count distinct days attended in this full week
                $daysAttendedThisWeek = $userLogs->filter(function ($log) use ($weekStart, $weekEnd) {
                    $logDate = \Carbon\Carbon::parse($log->date);
                    return $logDate->betweenIncluded($weekStart, $weekEnd);
                })->count();

                // Count permissions in this week (that are NOT already attended days - preventing double count if they punched AND had permission?)
                // Assuming Permission replaces Absence.
                // We simply add Permission count to Attended count for compliance purposes.
                // But we should check uniqueness? 
                // $logs contains DATES of attendance.
                // $userPermissions contains DATES of permission.
                // We should intersect/merge.

                $attendedDates = $userLogs->map(function ($l) {
                    return $l->date;
                })->unique()->toArray();
                $permissionDates = $userPermissions->map(function ($p) {
                    return $p->date->format('Y-m-d');
                })->toArray();

                $combinedDates = array_unique(array_merge($attendedDates, $permissionDates));

                // Filter combined to only this week
                $validDaysCount = 0;
                foreach ($combinedDates as $d) {
                    if (\Carbon\Carbon::parse($d)->betweenIncluded($weekStart, $weekEnd)) {
                        $validDaysCount++;
                    }
                }

                $weekAbsence = max(0, $requiredDays - $validDaysCount);

                $totalAbsences += $weekAbsence;
                $totalPresentDays += $validDaysCount;

                $weeksAnalyzed++;
                $current->addWeek();
            }

            $analytics[] = [
                'user_id' => $userId,
                'name' => $users[$userId] ?? 'Unknown (' . $userId . ')',
                'attended_days' => $totalPresentDays,
                'absences' => $totalAbsences,
                'weeks_count' => $weeksAnalyzed,
                'compliance_rate' => $weeksAnalyzed > 0 ? round(($totalPresentDays / ($requiredDays * $weeksAnalyzed)) * 100) : 0
            ];
        }

        usort($analytics, function ($a, $b) {
            return $b['absences'] <=> $a['absences'];
        });

        // Pass original request dates for the inputs, but logic uses aligned dates
        return view('attendance.analytics', compact('analytics', 'startDate', 'endDate', 'requiredDays'));
    }

    public function analyticsDetails(Request $request, $userId)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));

        $requiredDays = \App\Models\Setting::where('key', 'required_work_days')->value('value') ?? 3;

        $user = \App\Models\DeviceUser::where('user_id_on_device', $userId)->first();
        $userName = $user ? $user->name : 'Unknown (' . $userId . ')';

        // Align dates to full weeks (Sat - Fri)
        $start = \Carbon\Carbon::parse($startDate)->startOfWeek(\Carbon\Carbon::SATURDAY);
        $end = \Carbon\Carbon::parse($endDate)->endOfWeek(\Carbon\Carbon::FRIDAY);

        $logs = AttendanceLog::where('user_id_on_device', $userId)
            ->whereBetween('timestamp', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')])
            ->selectRaw('DATE(timestamp) as date')
            ->groupByRaw('DATE(timestamp)')
            ->havingRaw('COUNT(*) >= 2')
            ->get();

        $current = $start->copy();
        $weeklyDetails = [];

        while ($current->lte($end)) {
            $weekStart = $current->copy();
            $weekEnd = $current->copy()->endOfWeek(\Carbon\Carbon::FRIDAY);

            $daysAttended = $logs->filter(function ($log) use ($weekStart, $weekEnd) {
                $logDate = \Carbon\Carbon::parse($log->date);
                return $logDate->betweenIncluded($weekStart, $weekEnd);
            })->count();

            $absences = max(0, $requiredDays - $daysAttended);

            $weeklyDetails[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'attended_days' => $daysAttended,
                'required_days' => $requiredDays,
                'absences' => $absences,
                'status' => $absences > 0 ? 'Short by ' . $absences . ' day(s)' : 'Met Goal',
                'is_compliant' => $absences === 0
            ];

            $current->addWeek();
        }

        return view('attendance.analytics_user', compact(
            'weeklyDetails',
            'userName',
            'userId',
            'startDate',
            'endDate',
            'requiredDays'
        ));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'required_work_days' => 'required|integer|min:1|max:7',
        ]);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'required_work_days'],
            ['value' => $request->required_work_days]
        );

        return back()->with('success', 'Settings updated successfully.');
    }
    public function payroll(Request $request)
    {
        // Increase memory and time limit for large reports
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        // Default to current month
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));

        $requiredDays = \App\Models\Setting::where('key', 'required_work_days')->value('value') ?? 3;
        $schedules = \App\Models\Schedule::all()->keyBy('day_of_week');

        // Align dates to full weeks (Sat - Fri)
        $start = \Carbon\Carbon::parse($startDate)->startOfWeek(\Carbon\Carbon::SATURDAY);
        $end = \Carbon\Carbon::parse($endDate)->endOfWeek(\Carbon\Carbon::FRIDAY);

        $logs = AttendanceLog::with('device')
            ->whereBetween('timestamp', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')])
            ->orderBy('timestamp')
            ->get();

        // Fetch users and filter out those with numeric names (IDs disguised as names)
        $usersQuery = \App\Models\DeviceUser::query();

        if ($request->filled('search')) {
            $usersQuery->where('name', 'like', '%' . $request->search . '%');
        }

        $users = $usersQuery->pluck('name', 'user_id_on_device')
            ->filter(function ($name) {
                return !is_numeric($name);
            });

        $allUserIds = $users->keys();

        // Fetch all permissions for the range to optimize query count
        $allPermissions = \App\Models\Permission::whereIn('user_id_on_device', $allUserIds)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->groupBy('user_id_on_device');

        // 1. Build Weeks Structure
        $weeks = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $weekStart = $current->copy();
            $weekEnd = $current->copy()->endOfWeek(\Carbon\Carbon::FRIDAY);

            $weeks[] = [
                'start' => $weekStart->format('Y-m-d'),
                'end' => $weekEnd->format('Y-m-d'),
                'label' => $weekStart->format('d M') . ' - ' . $weekEnd->format('d M'),
            ];
            $current->addWeek();
        }

        // 2. Build User Data with Daily Slots
        $payrollData = [];
        $groupedLogs = $logs->groupBy('user_id_on_device');

        foreach ($allUserIds as $userId) {
            $userLogs = $groupedLogs[$userId] ?? collect();
            $userWeeks = [];

            $totalPresent = 0;
            $totalLate = 0;
            $totalAbsent = 0;
            $totalPermission = 0;

            // Get pre-fetched permissions for this user
            $userPermissionsCollection = $allPermissions[$userId] ?? collect();

            foreach ($weeks as $weekIndex => $week) {
                // Get logs for this week
                $weekStart = \Carbon\Carbon::parse($week['start']);
                $weekEnd = \Carbon\Carbon::parse($week['end']);

                $weekLogs = $userLogs->filter(function ($log) use ($weekStart, $weekEnd) {
                    return \Carbon\Carbon::parse($log->timestamp)->betweenIncluded($weekStart, $weekEnd);
                });

                // Group by day to find distinct attended days
                $daysLogs = $weekLogs->groupBy(function ($l) {
                    return \Carbon\Carbon::parse($l->timestamp)->format('Y-m-d');
                });

                // Filter permissions for this week
                $weekPermissions = $userPermissionsCollection->filter(function ($perm) use ($weekStart, $weekEnd) {
                    return $perm->date->betweenIncluded($weekStart, $weekEnd);
                })->keyBy(function ($item) {
                    return $item->date->format('Y-m-d');
                });

                // We need exactly $requiredDays slots
                $slots = [];

                $logDates = $daysLogs->keys()->toArray();
                $permDates = $weekPermissions->keys()->toArray();

                $allDates = array_unique(array_merge($logDates, $permDates));
                sort($allDates);

                // Values re-indexed
                $sortedDates = array_values($allDates);

                for ($i = 0; $i < $requiredDays; $i++) {
                    // Initialize slot data
                    $status = 'Absent';
                    $cssClass = 'danger';
                    $details = '-';

                    if (isset($sortedDates[$i])) {
                        $date = $sortedDates[$i];
                        $hasPerm = $weekPermissions->has($date);
                        $dayLogs = $daysLogs[$date] ?? collect();

                        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
                        $schedule = $schedules[$dayOfWeek] ?? null;

                        // Default checks
                        $status = 'Attend';
                        $cssClass = 'success';
                        $details = \Carbon\Carbon::parse($date)->format('D d');

                        if ($dayLogs->isEmpty()) {
                            // Only Permission exists for this date
                            if ($hasPerm) {
                                $status = 'Permission';
                                $cssClass = 'info';
                                $details = \Carbon\Carbon::parse($date)->format('D d') . ' (' . ($weekPermissions[$date]->reason ?? 'Excused') . ')';
                            } else {
                                $status = 'Absent';
                                $cssClass = 'danger';
                            }
                        } else {
                            // Has logs
                            $isAbsent = false;

                            if ($dayLogs->count() < 2) {
                                $isAbsent = true;
                                $details .= ' (Missing Punch)';
                            } elseif ($dayLogs->count() >= 2 && \Carbon\Carbon::parse($dayLogs->first()->timestamp)->format('H:i') === \Carbon\Carbon::parse($dayLogs->last()->timestamp)->format('H:i')) {
                                $isAbsent = true;
                                $details .= ' (Same Time)';
                            } elseif ($schedule && !$schedule->is_off_day) {
                                $checkIn = $dayLogs->first();
                                $scheduledStart = \Carbon\Carbon::parse($date . ' ' . $schedule->start_time);
                                if ($checkIn && \Carbon\Carbon::parse($checkIn->timestamp)->gt($scheduledStart->addMinutes(15))) {
                                    $status = 'Late';
                                    $cssClass = 'warning';
                                    $details .= ' (Late)';
                                }
                            }

                            if ($isAbsent) {
                                if ($hasPerm) {
                                    $status = 'Permission';
                                    $cssClass = 'info';
                                    $details = \Carbon\Carbon::parse($date)->format('D d') . ' (' . ($weekPermissions[$date]->reason ?? 'Permission') . ')';
                                } else {
                                    $status = 'Absent';
                                    $cssClass = 'danger';
                                }
                            }
                        }
                    } else {
                        // No data for this slot
                        $status = 'Absent';
                        $cssClass = 'danger';
                        $details = '-';
                    }

                    $slots[] = [
                        'status' => $status,
                        'class' => $cssClass,
                        'details' => $details
                    ];
                }

                $userWeeks[$weekIndex] = $slots;
            }

            // Recalculate totals
            foreach ($userWeeks as $slots) {
                foreach ($slots as $slot) {
                    if ($slot['status'] === 'Attend')
                        $totalPresent++;
                    elseif ($slot['status'] === 'Late')
                        $totalLate++;
                    elseif ($slot['status'] === 'Absent')
                        $totalAbsent++;
                    elseif ($slot['status'] === 'Permission')
                        $totalPermission++;
                }
            }

            $payrollData[] = [
                'user_id' => $userId,
                'name' => $users[$userId] ?? 'Unknown (' . $userId . ')',
                'summary' => [
                    'present' => $totalPresent,
                    'late' => $totalLate,
                    'absent' => $totalAbsent,
                    'permission' => $totalPermission
                ],
                'weeks' => $userWeeks
            ];
        }

        if ($request->has('export')) {
            $data = compact('weeks', 'payrollData', 'startDate', 'endDate', 'requiredDays');
            $filename = 'payroll_report_' . date('Y-m-d');

            if ($request->export == 'pdf') {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('attendance.payroll_export', $data);
                $pdf->setPaper('a4', 'landscape');
                return $pdf->download($filename . '.pdf');
            }

            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PayrollExport($data), $filename . '.xlsx');
        }

        return view('attendance.payroll', compact('weeks', 'payrollData', 'startDate', 'endDate', 'requiredDays'));
    }
}
