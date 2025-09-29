<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $table = 'shippings';

    protected $fillable = [
        'sale_id',
        'shop_id',
        'customer_id',
        'shipping_method',
        'carrier',
        'tracking_number',
        'status',
        'package_type',
        'package_weight',
        'package_dimensions',
        'insurance_amount',
        'shipping_cost',
        'handling_fee',
        'estimated_delivery_date',
        'actual_delivery_date',
        'shipped_at',
        'delivered_at',
        'returned_at',
        'from_address',
        'to_address',
        'label_url',
        'tracking_events',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'package_weight' => 'decimal:3',
        'package_dimensions' => 'array',
        'insurance_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'handling_fee' => 'decimal:2',
        'estimated_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'from_address' => 'array',
        'to_address' => 'array',
        'tracking_events' => 'array',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'status' => 'pending',
        'package_type' => 'package',
        'shipping_cost' => 0,
        'handling_fee' => 0,
        'insurance_amount' => 0
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function carrierAccount()
    {
        return $this->belongsTo(CarrierAccount::class, 'carrier', 'code');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeByCarrier($query, $carrier)
    {
        return $query->where('carrier', $carrier);
    }

    public function scopeLate($query)
    {
        return $query->where('estimated_delivery_date', '<', Carbon::now())
                    ->whereNotIn('status', ['delivered', 'returned', 'failed']);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->whereIn('status', ['failed', 'returned'])
                    ->orWhere(function ($q) {
                        $q->where('estimated_delivery_date', '<', Carbon::now())
                          ->whereNotIn('status', ['delivered', 'returned']);
                    });
    }

    // Methods
    public function getTotalShippingCost()
    {
        return $this->shipping_cost + $this->handling_fee + $this->insurance_amount;
    }

    public function markAsShipped($trackingNumber = null, $shippedAt = null)
    {
        $this->update([
            'status' => 'shipped',
            'tracking_number' => $trackingNumber ?? $this->tracking_number,
            'shipped_at' => $shippedAt ?? Carbon::now()
        ]);

        // Update sale status if needed
        if ($this->sale && $this->sale->status === 'confirmed') {
            $this->sale->markAsCompleted();
        }
    }

    public function markAsInTransit()
    {
        $this->update(['status' => 'in_transit']);
    }

    public function markAsDelivered($deliveredAt = null)
    {
        $this->update([
            'status' => 'delivered',
            'actual_delivery_date' => $deliveredAt ?? Carbon::now(),
            'delivered_at' => $deliveredAt ?? Carbon::now()
        ]);
    }

    public function markAsFailed($reason = '')
    {
        $this->update([
            'status' => 'failed',
            'notes' => $this->notes . "\nFailed: " . $reason
        ]);
    }

    public function markAsReturned($returnedAt = null, $reason = '')
    {
        $this->update([
            'status' => 'returned',
            'returned_at' => $returnedAt ?? Carbon::now(),
            'notes' => $this->notes . "\nReturned: " . $reason
        ]);
    }

    public function addTrackingEvent($event, $location = null, $timestamp = null)
    {
        $events = $this->tracking_events ?? [];
        
        $events[] = [
            'event' => $event,
            'location' => $location,
            'timestamp' => $timestamp ?: Carbon::now()->toISOString(),
            'recorded_at' => Carbon::now()->toISOString()
        ];

        $this->update(['tracking_events' => $events]);
    }

    public function getLatestTrackingEvent()
    {
        $events = $this->tracking_events ?? [];
        return !empty($events) ? end($events) : null;
    }

    public function getTrackingHistory()
    {
        return collect($this->tracking_events ?? [])
            ->sortBy('timestamp')
            ->values()
            ->all();
    }

    public function calculateEstimatedDelivery()
    {
        if (!$this->shipped_at || !$this->carrier) {
            return null;
        }

        // This would integrate with carrier APIs in production
        // For now, using simple business logic
        $baseDays = $this->getBaseTransitDays();
        $estimatedDate = $this->shipped_at->copy()->addWeekdays($baseDays);
        
        $this->update(['estimated_delivery_date' => $estimatedDate]);
        
        return $estimatedDate;
    }

    private function getBaseTransitDays()
    {
        $transitDays = [
            'fedex' => 3,
            'ups' => 3,
            'dhl' => 4,
            'usps' => 5,
            'local' => 1,
            'pickup' => 0,
            'custom' => 2
        ];

        return $transitDays[strtolower($this->carrier)] ?? 3;
    }

    public function isDelayed()
    {
        if (!$this->estimated_delivery_date || $this->status === 'delivered') {
            return false;
        }

        return $this->estimated_delivery_date->isPast();
    }

    public function getDelayDays()
    {
        if (!$this->isDelayed()) {
            return 0;
        }

        return $this->estimated_delivery_date->diffInDays(Carbon::now());
    }

    public function generateTrackingUrl()
    {
        if (!$this->tracking_number || !$this->carrier) {
            return null;
        }

        $carrierUrls = [
            'fedex' => "https://www.fedex.com/fedextrack/?trknbr={tracking}",
            'ups' => "https://www.ups.com/track?tracknum={tracking}",
            'dhl' => "https://www.dhl.com/en/express/tracking.html?AWB={tracking}",
            'usps' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking}",
        ];

        $url = $carrierUrls[strtolower($this->carrier)] ?? null;
        
        return $url ? str_replace('{tracking}', $this->tracking_number, $url) : null;
    }

    public function validateAddress()
    {
        $toAddress = $this->to_address;
        
        if (!$toAddress) {
            return false;
        }

        $requiredFields = ['address_line1', 'city', 'postal_code', 'country'];
        
        foreach ($requiredFields as $field) {
            if (empty($toAddress[$field])) {
                return false;
            }
        }

        return true;
    }

    public function getPackageVolume()
    {
        $dimensions = $this->package_dimensions;
        
        if (!$dimensions || !isset($dimensions['length']) || !isset($dimensions['width']) || !isset($dimensions['height'])) {
            return 0;
        }

        return $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
    }

    public function calculateShippingCost($carrierRates = [])
    {
        if (!empty($carrierRates)) {
            // Use provided carrier rates
            $this->shipping_cost = $carrierRates['cost'] ?? 0;
            $this->insurance_amount = $carrierRates['insurance'] ?? 0;
        } else {
            // Calculate based on weight and dimensions
            $baseCost = 5.00; // Base shipping cost
            $weightCost = $this->package_weight * 0.5; // $0.50 per kg
            $volumeCost = $this->getPackageVolume() * 0.01; // $0.01 per cm³
            
            $this->shipping_cost = $baseCost + $weightCost + $volumeCost;
            $this->insurance_amount = $this->insurance_amount ?: 0;
        }

        $this->save();
        return $this->getTotalShippingCost();
    }

    public static function createFromSale(Sale $sale, $shippingData = [])
    {
        $customer = $sale->customer;
        
        $shippingAddress = $customer->shipping_address ?: $customer->billing_address;
        $shopAddress = $sale->shop->address;

        $defaultData = [
            'shop_id' => $sale->shop_id,
            'customer_id' => $sale->customer_id,
            'from_address' => [
                'name' => $sale->shop->name,
                'address_line1' => $shopAddress['address_line1'] ?? '',
                'address_line2' => $shopAddress['address_line2'] ?? '',
                'city' => $shopAddress['city'] ?? '',
                'state' => $shopAddress['state'] ?? '',
                'postal_code' => $shopAddress['postal_code'] ?? '',
                'country' => $shopAddress['country'] ?? 'Kenya',
                'phone' => $sale->shop->phone,
                'email' => $sale->shop->email
            ],
            'to_address' => [
                'name' => $customer->name,
                'address_line1' => $shippingAddress['address_line1'] ?? '',
                'address_line2' => $shippingAddress['address_line2'] ?? '',
                'city' => $shippingAddress['city'] ?? '',
                'state' => $shippingAddress['state'] ?? '',
                'postal_code' => $shippingAddress['postal_code'] ?? '',
                'country' => $shippingAddress['country'] ?? 'Kenya',
                'phone' => $customer->phone,
                'email' => $customer->email
            ],
            'shipping_method' => 'standard',
            'carrier' => 'local',
            'status' => 'pending'
        ];

        return $sale->shipping()->create(array_merge($defaultData, $shippingData));
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'confirmed']) && !$this->shipped_at;
    }

    public function cancel($reason = '')
    {
        if ($this->canBeCancelled()) {
            $this->update([
                'status' => 'cancelled',
                'notes' => $this->notes . "\nCancelled: " . $reason
            ]);
            return true;
        }
        return false;
    }
}