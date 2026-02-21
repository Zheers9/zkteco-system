<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'day_of_week',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'is_off_day',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_off_day' => 'boolean',
    ];

    /**
     * Find the active schedule for a given date.
     * Priority: Specific schedule (with date range) > Default schedule (no date range)
     */
    public static function forDate($date)
    {
        $carbonDate = \Carbon\Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek;
        $dateStr = $carbonDate->format('Y-m-d');

        // 1. Try to find a schedule with a date range that includes this date
        $special = self::where('day_of_week', $dayOfWeek)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();

        if ($special) {
            return $special;
        }

        // 2. Fallback to default schedule (where start_date is null)
        return self::where('day_of_week', $dayOfWeek)
            ->whereNull('start_date')
            ->first();
    }

    /**
     * Get the day name string (e.g., "Monday")
     */
    public function getDayNameAttribute()
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        return $days[$this->day_of_week] ?? 'Unknown';
    }
}
