<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DeviceUser;

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

    public function deviceUser()
    {
        // Match by user_id_on_device only — device context is already
        // guaranteed because we query logs per device in the controller.
        return $this->belongsTo(DeviceUser::class, 'user_id_on_device', 'user_id_on_device');
    }
}
