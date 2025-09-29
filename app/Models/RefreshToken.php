<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'revoked_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isRevoked()
    {
        return !is_null($this->revoked_at);
    }

    public function revoke()
    {
        $this->update(['revoked_at' => Carbon::now()]);
    }
}