<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscriptions';
    protected $fillable = [
        'shop_id',
        'plan_id',
        'name',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        'ends_at',
        'canceled_at',
        'metadata'
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('ends_at')
              ->orWhere('ends_at', '>', Carbon::now());
        })->where(function ($q) {
            $q->whereNull('canceled_at')
              ->orWhere('canceled_at', '>', Carbon::now());
        });
    }

    public function scopeCanceled($query)
    {
        return $query->whereNotNull('canceled_at')
                    ->where('canceled_at', '<=', Carbon::now());
    }

    public function scopeOnTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
                    ->where('trial_ends_at', '>', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('ends_at')
                    ->where('ends_at', '<=', Carbon::now());
    }

    // Methods
    public function active()
    {
        return (is_null($this->ends_at) || $this->ends_at->isFuture()) &&
               (is_null($this->canceled_at) || $this->canceled_at->isFuture());
    }

    public function canceled()
    {
        return ! is_null($this->canceled_at) && $this->canceled_at->isPast();
    }

    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function onGracePeriod()
    {
        return $this->canceled_at && $this->canceled_at->isFuture();
    }

    public function hasIncompletePayment()
    {
        return $this->stripe_status === 'incomplete';
    }

    public function cancel()
    {
        $this->update(['canceled_at' => Carbon::now()]);

        // If the shop was on trial, we will set the grace period to end when the trial would have ended.
        if ($this->onTrial()) {
            $this->update(['ends_at' => $this->trial_ends_at]);
        } else {
            $this->update(['ends_at' => Carbon::now()->addDays(7)]); // 7-day grace period
        }
    }

    public function cancelNow()
    {
        $this->update([
            'canceled_at' => Carbon::now(),
            'ends_at' => Carbon::now()
        ]);
    }

    public function resume()
    {
        $this->update([
            'canceled_at' => null,
            'ends_at' => null
        ]);
    }

    public function swap($planId)
    {
        $this->update(['plan_id' => $planId]);
    }

    public function incrementQuantity($count = 1)
    {
        $this->update(['quantity' => $this->quantity + $count]);
    }

    public function decrementQuantity($count = 1)
    {
        $this->update(['quantity' => max(1, $this->quantity - $count)]);
    }
}