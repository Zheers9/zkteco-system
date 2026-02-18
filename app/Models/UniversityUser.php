<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversityUser extends Model
{
    protected $fillable = [
        'name',
        'user_sid',
        'device_user_id',
    ];

    /**
     * Get the device user associated with this university user
     */
    public function deviceUser()
    {
        return $this->belongsTo(DeviceUser::class, 'device_user_id');
    }

    /**
     * Check if this university user is assigned to a device user
     */
    public function isAssigned()
    {
        return !is_null($this->device_user_id);
    }
}
