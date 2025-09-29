<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'stripe_plan_id',
        'stripe_price_id',
        'type',
        'price',
        'currency',
        'billing_interval',
        'billing_interval_count',
        'trial_days',
        'sort_order',
        'is_active',
        'is_featured',
        'is_visible',
        'features',
        'limits',
        'metadata'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_visible' => 'boolean',
        'features' => 'array',
        'limits' => 'array',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'type' => 'subscription',
        'currency' => 'KES',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
        'trial_days' => 0,
        'sort_order' => 0,
        'is_active' => true,
        'is_featured' => false,
        'is_visible' => true
    ];

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByInterval($query, $interval)
    {
        return $query->where('billing_interval', $interval);
    }

    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // Methods
    public function getFormattedPrice()
    {
        if ($this->price == 0) {
            return 'Free';
        }

        $currencySymbols = [
            'KES' => 'KSh',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        ];

        $symbol = $currencySymbols[$this->currency] ?? $this->currency;
        
        if ($this->billing_interval === 'month') {
            return $symbol . number_format($this->price, 2) . '/month';
        } elseif ($this->billing_interval === 'year') {
            return $symbol . number_format($this->price, 2) . '/year';
        } else {
            return $symbol . number_format($this->price, 2);
        }
    }

    public function getYearlyPrice()
    {
        if ($this->billing_interval === 'year') {
            return $this->price;
        } elseif ($this->billing_interval === 'month') {
            return $this->price * 12;
        }
        return $this->price;
    }

    public function hasFeature($feature)
    {
        $features = $this->features ?? [];
        return in_array($feature, $features);
    }

    public function getLimit($limitKey)
    {
        $limits = $this->limits ?? [];
        return $limits[$limitKey] ?? null;
    }

    public function isFree()
    {
        return $this->price == 0;
    }

    public function isRecurring()
    {
        return in_array($this->type, ['subscription', 'recurring']);
    }

    public function getBillingCycleDescription()
    {
        if ($this->billing_interval === 'month') {
            return 'Monthly';
        } elseif ($this->billing_interval === 'year') {
            return 'Yearly';
        } elseif ($this->billing_interval === 'week') {
            return 'Weekly';
        } elseif ($this->billing_interval === 'day') {
            return 'Daily';
        } else {
            return 'One-time';
        }
    }

    public function getTrialPeriodDescription()
    {
        if ($this->trial_days > 0) {
            return $this->trial_days . ' day' . ($this->trial_days > 1 ? 's' : '') . ' free trial';
        }
        return 'No trial';
    }

    public function canUpgradeTo(Plan $newPlan)
    {
        if ($this->isFree() && !$newPlan->isFree()) {
            return true;
        }

        if ($this->price < $newPlan->price) {
            return true;
        }

        return false;
    }

    public function canDowngradeTo(Plan $newPlan)
    {
        if (!$this->isFree() && $newPlan->isFree()) {
            return true;
        }

        if ($this->price > $newPlan->price) {
            return true;
        }

        return false;
    }

    public static function getDefaultPlan()
    {
        return static::active()->visible()->free()->ordered()->first();
    }

    public function getStripePriceData()
    {
        return [
            'currency' => $this->currency,
            'unit_amount' => (int) ($this->price * 100), // Convert to cents
            'recurring' => $this->isRecurring() ? [
                'interval' => $this->billing_interval,
                'interval_count' => $this->billing_interval_count
            ] : null,
            'product_data' => [
                'name' => $this->name,
                'description' => $this->description,
                'metadata' => [
                    'plan_id' => $this->id,
                    'type' => $this->type
                ]
            ]
        ];
    }

    public function syncWithStripe()
    {
        // This would integrate with Stripe to create/update the plan
        // For now, just update the local stripe IDs if needed
        if (!$this->stripe_plan_id) {
            $this->stripe_plan_id = 'plan_' . uniqid();
        }
        if (!$this->stripe_price_id) {
            $this->stripe_price_id = 'price_' . uniqid();
        }
        $this->save();
    }
}