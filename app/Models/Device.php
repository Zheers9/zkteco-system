<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip',
        'port',
        'location',
        'status',
        'last_connected_at'
    ];

    protected $casts = [
        'last_connected_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(DeviceUser::class);
    }

    public function logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
