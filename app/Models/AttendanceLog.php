<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'uid',
        'user_id_on_device',
        'timestamp',
        'status',
        'type'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
