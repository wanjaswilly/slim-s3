<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CarrierAccount extends Model
{
    protected $table = 'carrieraccounts';

    protected $fillable = [
        'shop_id',
        'carrier_name',
        'code',
        'account_number',
        'api_key',
        'api_secret',
        'test_mode',
        'is_active',
        'credentials',
        'settings',
        'metadata'
    ];

    protected $casts = [
        'test_mode' => 'boolean',
        'is_active' => 'boolean',
        'credentials' => 'encrypted',
        'settings' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function shippings()
    {
        return $this->hasMany(Shipping::class, 'carrier', 'code');
    }

    // Methods
    public function getRates($packageData)
    {
        // This would integrate with carrier API
        // For now, return mock rates
        return [
            'standard' => [
                'cost' => 10.00,
                'estimated_days' => 3,
                'service' => 'Standard'
            ],
            'express' => [
                'cost' => 20.00,
                'estimated_days' => 1,
                'service' => 'Express'
            ],
            'overnight' => [
                'cost' => 35.00,
                'estimated_days' => 1,
                'service' => 'Overnight'
            ]
        ];
    }

    public function createShipment($shippingData)
    {
        // Integrate with carrier API to create shipment
        // Return tracking number and label URL
        return [
            'tracking_number' => 'TRK' . time(),
            'label_url' => 'https://example.com/labels/' . uniqid() . '.pdf',
            'cost' => $shippingData['cost'] ?? 0
        ];
    }

    public function trackShipment($trackingNumber)
    {
        // Integrate with carrier API to get tracking info
        return [
            'status' => 'in_transit',
            'events' => [
                [
                    'event' => 'Shipped',
                    'location' => 'Nairobi, Kenya',
                    'timestamp' => Carbon::now()->subDays(2)->toISOString()
                ],
                [
                    'event' => 'In Transit',
                    'location' => 'Mombasa, Kenya',
                    'timestamp' => Carbon::now()->subDays(1)->toISOString()
                ]
            ],
            'estimated_delivery' => Carbon::now()->addDays(1)->toISOString()
        ];
    }
}
