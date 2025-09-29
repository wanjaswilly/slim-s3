<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'email_verified_at',
        'phone_verified_at',
        'timezone',
        'language',
        'currency',
        'avatar_url',
        'timezone',
        'last_login_at',
        'last_login_ip',
        'last_active_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_active_at' => 'datetime',
        'is_active' => 'boolean',
        'meta' => 'array',
        'preferences' => 'array',
    ];

    protected $attributes = [
        'role' => 'seller',
        'is_active' => true,
        'language' => 'en',
        'currency' => 'KES',
        'timezone' => 'Africa/Nairobi',
    ];

    // Relationships
    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'user_shop_members')
                    ->withPivot(['role', 'permissions', 'is_active', 'joined_at'])
                    ->withTimestamps();
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function ownedShops()
    {
        return $this->hasMany(Shop::class, 'owner_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'seller_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSellers($query)
    {
        return $query->where('role', 'seller');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeWithEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeWithPhone($query, $phone)
    {
        return $query->where('phone', $phone);
    }

    public function scopeRecentlyActive($query, $days = 7)
    {
        return $query->where('last_active_at', '>=', Carbon::now()->subDays($days));
    }

    // Methods
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    public function hasVerifiedPhone()
    {
        return !is_null($this->phone_verified_at);
    }

    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function markPhoneAsVerified()
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function updateLastLogin($ipAddress = null)
    {
        $this->update([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $ipAddress,
            'last_active_at' => Carbon::now(),
        ]);
    }

    public function updateLastActive()
    {
        $this->update(['last_active_at' => Carbon::now()]);
    }

    public function getAvatarUrlAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Generate default avatar based on name
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=0D8ABC&color=fff";
    }

    public function hasSocialAccount($provider)
    {
        return $this->socialAccounts()->where('provider', $provider)->exists();
    }

    public function getSocialAccount($provider)
    {
        return $this->socialAccounts()->where('provider', $provider)->first();
    }

    public function isShopOwner($shopId = null)
    {
        if ($shopId) {
            return $this->ownedShops()->where('id', $shopId)->exists();
        }

        return $this->ownedShops()->exists();
    }

    public function isShopMember($shopId)
    {
        return $this->shops()->where('shops.id', $shopId)->exists();
    }

    public function getShopRole($shopId)
    {
        $shop = $this->shops()->where('shops.id', $shopId)->first();
        return $shop ? $shop->pivot->role : null;
    }

    public function can($permission, $shopId = null)
    {
        // Super admin has all permissions
        if ($this->role === 'super_admin') {
            return true;
        }

        // Shop-specific permissions
        if ($shopId) {
            $shop = $this->shops()->where('shops.id', $shopId)->first();
            if ($shop) {
                $permissions = json_decode($shop->pivot->permissions ?? '[]', true);
                return in_array($permission, $permissions);
            }
        }

        // Global role-based permissions
        return $this->hasRolePermission($permission);
    }

    protected function hasRolePermission($permission)
    {
        $rolePermissions = [
            'admin' => ['manage_users', 'manage_shops', 'view_reports'],
            'seller' => ['manage_products', 'manage_sales', 'view_analytics'],
            'staff' => ['view_products', 'process_sales'],
        ];

        return in_array($permission, $rolePermissions[$this->role] ?? []);
    }

    public function getPreferredLanguage()
    {
        return $this->language ?? 'en';
    }

    public function getPreferredCurrency()
    {
        return $this->currency ?? 'KES';
    }

    public function getTimezone()
    {
        return $this->timezone ?? 'Africa/Nairobi';
    }

    // Social login methods
    public function createSocialAccount($provider, $providerId, $token, $refreshToken = null, $expiresIn = null)
    {
        return $this->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => $providerId,
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null,
        ]);
    }

    public static function findBySocialAccount($provider, $providerId)
    {
        return static::whereHas('socialAccounts', function ($query) use ($provider, $providerId) {
            $query->where('provider', $provider)->where('provider_id', $providerId);
        })->first();
    }

    public static function findByEmailOrPhone($email, $phone = null)
    {
        return static::where('email', $email)
                    ->orWhere('phone', $phone)
                    ->first();
    }

    public static function createFromSocialProvider($request, $provider, $userData, )
    {
        return static::create([
            'name' => $userData['name'] ?? 'Social User',
            'email' => $userData['email'] ?? null,
            'phone' => $userData['phone'] ?? null,
            'avatar_url' => $userData['avatar'] ?? null,
            'email_verified_at' => $userData['email_verified'] ? Carbon::now() : null,
            'password' =>  password_hash(uniqid(), PASSWORD_BCRYPT), // Random password for social users
            'role' => 'seller',
            'is_active' => true,
            'meta' => [
                'social_signup' => true,
                'signup_provider' => $provider,
                'signup_ip' => $request->getServerParams()['REMOTE_ADDR'] ?? null,
            ]
        ]);
    }
}