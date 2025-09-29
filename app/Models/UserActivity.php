<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    protected $table = 'useractivitys';
    protected $fillable = [
        'user_id',
        'shop_id',
        'type',
        'description',
        'ip_address',
        'user_agent',
        'device_type',
        'location',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'metadata'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'location' => 'array'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function resource()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeThisWeek($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->startOfWeek());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->startOfMonth());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    // Methods
    public static function log($request, $userId, $type, $description, $shopId = null, $resource = null, $oldValues = null, $newValues = null)
    {
        $location = static::getLocationData();

        return static::create([
            'user_id' => $userId,
            'shop_id' => $shopId,
            'type' => $type,
            'description' => $description,
            'ip_address' => $request->getServerParams()['REMOTE_ADDR'] ?? null,
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'device_type' => $request->getHeaderLine('User-Agent') ?: 'unknown',
            'location' => $location,
            'resource_type' => $resource ? get_class($resource) : null,
            'resource_id' => $resource ? $resource->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'url' => (string) $request->getUri(),
                'method' => $request->getMethod()
            ]
        ]);
    }

    private static function getLocationData()
    {
        // In production, you might use a geolocation service
        return [
            'country' => null,
            'city' => null,
            'region' => null,
            'timezone' => [],
        ];
    }

    private static function getDeviceType($request)
    {
        $agent = $request->getHeaderLine('User-Agent') ?: 'unknown';
        
        if (preg_match('/(mobile|android|iphone|ipad)/i', $agent)) {
            return 'mobile';
        } elseif (preg_match('/(tablet|ipad)/i', $agent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    public function getChanges()
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }

    public function hassChanges()
    {
        return !empty($this->getChanges());
    }
}