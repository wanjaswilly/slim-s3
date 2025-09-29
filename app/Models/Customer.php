<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'shop_id',
        'name',
        'email',
        'phone',
        'customer_type',
        'company_name',
        'tax_number',
        'currency',
        'language',
        'timezone',
        'billing_address',
        'shipping_address',
        'notes',
        'tags',
        'loyalty_points',
        'total_spent',
        'last_purchase_at',
        'metadata'
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'tags' => 'array',
        'loyalty_points' => 'integer',
        'total_spent' => 'decimal:2',
        'last_purchase_at' => 'datetime',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'customer_type' => 'individual',
        'currency' => 'KES',
        'language' => 'en',
        'timezone' => 'Africa/Nairobi',
        'loyalty_points' => 0,
        'total_spent' => 0
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereHas('sales', function ($q) {
            $q->where('created_at', '>=', Carbon::now()->subMonths(6));
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('customer_type', $type);
    }

    public function scopeWithEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeWithPhone($query, $phone)
    {
        return $query->where('phone', $phone);
    }

    public function scopeHighValue($query, $threshold = 10000)
    {
        return $query->where('total_spent', '>=', $threshold);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('last_purchase_at', '>=', Carbon::now()->subDays($days));
    }

    // Methods
    public function getFullAddress($type = 'billing')
    {
        $address = $type === 'shipping' ? $this->shipping_address : $this->billing_address;

        if (!$address) {
            return null;
        }

        $parts = [
            $address['address_line1'] ?? '',
            $address['address_line2'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? ''
        ];

        return implode(', ', array_filter($parts));
    }

    public function updateTotalSpent()
    {
        $total = $this->sales()
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $this->update([
            'total_spent' => $total,
            'last_purchase_at' => $this->sales()->latest()->first()->sale_date ?? $this->last_purchase_at
        ]);
    }

    public function addLoyaltyPoints($points, $reason = 'purchase')
    {
        $this->increment('loyalty_points', $points);

        $this->loyaltyTransactions()->create([
            'points' => $points,
            'type' => 'earned',
            'reason' => $reason,
            'balance' => $this->loyalty_points
        ]);
    }

    public function redeemLoyaltyPoints($points, $reason = 'redemption')
    {
        if ($this->loyalty_points >= $points) {
            $this->decrement('loyalty_points', $points);

            $this->loyaltyTransactions()->create([
                'points' => $points,
                'type' => 'redeemed',
                'reason' => $reason,
                'balance' => $this->loyalty_points
            ]);

            return true;
        }

        return false;
    }

    public function getAverageOrderValue()
    {
        $completedSales = $this->sales()
            ->where('status', 'completed')
            ->where('payment_status', 'paid');

        $count = $completedSales->count();
        $total = $completedSales->sum('total_amount');

        return $count > 0 ? $total / $count : 0;
    }

    public function getPurchaseFrequency()
    {
        $firstPurchase = $this->sales()->orderBy('sale_date')->first();
        $lastPurchase = $this->sales()->orderBy('sale_date', 'desc')->first();

        if (!$firstPurchase || !$lastPurchase) {
            return 0;
        }

        $daysBetween = $firstPurchase->sale_date->diffInDays($lastPurchase->sale_date);
        $totalPurchases = $this->sales()->count();

        return $daysBetween > 0 ? $totalPurchases / ($daysBetween / 30) : $totalPurchases; // Purchases per month
    }

    public function isNewCustomer()
    {
        return !$this->last_purchase_at || $this->last_purchase_at->diffInDays(Carbon::now()) <= 30;
    }

    public static function findOrCreateByPhone($shopId, $phone, $data = [])
    {
        $customer = static::where('shop_id', $shopId)
            ->where('phone', $phone)
            ->first();

        if (!$customer) {
            $customer = static::create(array_merge([
                'shop_id' => $shopId,
                'phone' => $phone,
                'name' => $data['name'] ?? 'Customer',
                'customer_type' => 'individual'
            ], $data));
        }

        return $customer;
    }

    public function getCustomerTier()
    {
        if ($this->total_spent >= 50000) {
            return 'premium';
        } elseif ($this->total_spent >= 10000) {
            return 'gold';
        } elseif ($this->total_spent >= 1000) {
            return 'silver';
        } else {
            return 'standard';
        }
    }
}
