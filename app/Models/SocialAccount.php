<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'token',
        'refresh_token',
        'expires_at',
        'provider_data'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'provider_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function updateToken($token, $refreshToken = null, $expiresIn = null)
    {
        $this->update([
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null,
        ]);
    }
}