<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        // Default schedules (no date range)
        $defaults = Schedule::whereNull('start_date')
            ->orderBy('day_of_week')
            ->get();

        // Ensure all days 0-6 exist for defaults
        if ($defaults->count() < 7) {
            $now = now();
            for ($i = 0; $i <= 6; $i++) {
                if (!$defaults->contains('day_of_week', $i)) {
                    Schedule::create([
                        'name' => 'Default',
                        'day_of_week' => $i,
                        'start_time' => '08:00',
                        'end_time' => '16:00',
                        'is_off_day' => ($i === 5),
                    ]);
                }
            }
            $defaults = Schedule::whereNull('start_date')->orderBy('day_of_week')->get();
        }

        // Special periods grouped by their unique properties (name, start_date, end_date)
        $periods = Schedule::whereNotNull('start_date')
            ->get()
            ->groupBy(function ($item) {
                return $item->name . '|' . $item->start_date->format('Y-m-d') . '|' . $item->end_date->format('Y-m-d');
            });

        return view('schedules.index', compact('defaults', 'periods'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|exists:schedules,id',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
        ]);

        foreach ($request->schedules as $scheduleData) {
            $schedule = Schedule::find($scheduleData['id']);
            $schedule->update([
                'start_time' => $scheduleData['start_time'] ?? null,
                'end_time' => $scheduleData['end_time'] ?? null,
                'is_off_day' => isset($scheduleData['is_off_day']),
            ]);
        }

        return redirect()->route('schedules.index')->with('success', 'Schedule updated successfully.');
    }

    public function storePeriod(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Create 7 rows for this period
        for ($i = 0; $i <= 6; $i++) {
            Schedule::create([
                'name' => $data['name'],
                'day_of_week' => $i,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'start_time' => '08:00',
                'end_time' => '16:00',
                'is_off_day' => ($i === 5),
            ]);
        }

        return redirect()->route('schedules.index')->with('success', 'Special period created. You can now edit its specific times.');
    }

    public function destroyPeriod(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        Schedule::where('name', $request->name)
            ->where('start_date', $request->start_date)
            ->where('end_date', $request->end_date)
            ->delete();

        return redirect()->route('schedules.index')->with('success', 'Special period deleted.');
    }
}
