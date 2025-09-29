<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    protected $table = 'shops';

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'business_type',
        'category',
        'logo_url',
        'cover_url',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'timezone',
        'currency',
        'language',
        'tax_rate',
        'tax_number',
        'business_registration_number',
        'is_verified',
        'is_active',
        'is_featured',
        'settings',
        'social_links',
        'operating_hours',
        'metadata'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'tax_rate' => 'decimal:2',
        'latitude' => 'decimal:10,8',
        'longitude' => 'decimal:11,8',
        'settings' => 'array',
        'social_links' => 'array',
        'operating_hours' => 'array',
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'user_shop_members')
                    ->withPivot(['role', 'permissions', 'is_active', 'joined_at'])
                    ->withTimestamps();
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function channels()
    {
        return $this->hasMany(Channel::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function analytics()
    {
        return $this->hasMany(Analytics::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 10)
    {
        return $query->whereRaw("
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
            sin(radians(latitude)))) < ?
        ", [$latitude, $longitude, $latitude, $radius]);
    }

    // Methods
    public function getSettingsAttribute($value)
    {
        $defaultSettings = [
            'notifications' => [
                'email' => true,
                'sms' => true,
                'push' => true,
            ],
            'inventory' => [
                'low_stock_threshold' => 5,
                'auto_restock' => false,
            ],
            'sales' => [
                'auto_confirm' => true,
                'require_payment_confirmation' => true,
            ],
            'social' => [
                'auto_post' => false,
                'cross_post' => true,
            ]
        ];

        $settings = $value ? json_decode($value, true) : [];
        return array_merge($defaultSettings, $settings);
    }

    public function getSocialLinksAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function getOperatingHoursAttribute($value)
    {
        $defaultHours = [
            'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'saturday' => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
            'sunday' => ['open' => '00:00', 'close' => '00:00', 'closed' => true],
        ];

        $hours = $value ? json_decode($value, true) : [];
        return array_merge($defaultHours, $hours);
    }

    public function isOpen()
    {
        $now = Carbon::now($this->timezone ?? null);
        $day = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');
        
        $hours = $this->operating_hours[$day] ?? null;
        
        if (!$hours || $hours['closed']) {
            return false;
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    public function verify()
    {
        return $this->update([
            'is_verified' => true,
            'verified_at' => Carbon::now()
        ]);
    }

    public function getTotalSales()
    {
        return $this->sales()->where('status', 'completed')->sum('total_amount');
    }

    public function getMonthlySales()
    {
        return $this->sales()
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonth())
            ->sum('total_amount');
    }

    public function getActiveProductsCount()
    {
        return $this->products()->where('is_active', true)->count();
    }

    public function addMember($userId, $role = 'staff', $permissions = [])
    {
        return $this->members()->attach($userId, [
            'role' => $role,
            'permissions' => json_encode($permissions),
            'joined_at' => Carbon::now(),
            'is_active' => true
        ]);
    }

    public function removeMember($userId)
    {
        return $this->members()->detach($userId);
    }
}