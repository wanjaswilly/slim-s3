<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $table = 'channels';
    protected $fillable = [
        'shop_id',
        'type',
        'name',
        'identifier',
        'credentials',
        'settings',
        'status',
        'is_active',
        'last_sync_at',
        'error_message',
        'metadata'
    ];

    protected $casts = [
        'credentials' => 'encrypted',
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    // Methods
    public function isConnected()
    {
        return $this->status === 'connected';
    }

    public function markAsConnected()
    {
        $this->update([
            'status' => 'connected',
            'error_message' => null
        ]);
    }

    public function markAsDisconnected($error = null)
    {
        $this->update([
            'status' => 'disconnected',
            'error_message' => $error
        ]);
    }

    public function updateLastSync()
    {
        $this->update(['last_sync_at' => Carbon::now()]);
    }

    public function getCredential($key)
    {
        $credentials = $this->credentials ? json_decode($this->credentials, true) : [];
        return $credentials[$key] ?? null;
    }

    public function setCredential($key, $value)
    {
        $credentials = $this->credentials ? json_decode($this->credentials, true) : [];
        $credentials[$key] = $value;
        $this->credentials = json_encode($credentials);
        $this->save();
    }
}