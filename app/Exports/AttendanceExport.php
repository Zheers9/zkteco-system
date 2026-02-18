<?php

namespace App\Exports;

use App\Models\AttendanceLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Http\Request;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = AttendanceLog::query();

        if ($this->request->has('start_date') && $this->request->start_date) {
            $query->whereDate('timestamp', '>=', $this->request->start_date);
        }

        if ($this->request->has('end_date') && $this->request->end_date) {
            $query->whereDate('timestamp', '<=', $this->request->end_date);
        }

        return $query->orderBy('timestamp', 'desc');
    }

    public function headings(): array
    {
        return [
            'Device ID',
            'UID',
            'User ID',
            'Timestamp',
            'Status',
            'Type'
        ];
    }

    public function map($log): array
    {
        return [
            $log->device_id,
            $log->uid,
            $log->user_id_on_device,
            $log->timestamp,
            $log->status,
            $log->type
        ];
    }
}
