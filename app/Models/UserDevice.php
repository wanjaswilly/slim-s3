<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_type',
        'push_token',
        'platform',
        'os_version',
        'app_version',
        'last_used_at',
        'is_active'
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'meta' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}