<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::orderBy('day_of_week')->get();
        // Ensure all days 0-6 exist
        if ($schedules->count() < 7) {
            // Should be seeded but just in case
        }
        return view('schedules.index', compact('schedules'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|exists:schedules,id',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
            'schedules.*.is_off_day' => 'boolean', // Checkbox sends 1 or not present?
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
}
