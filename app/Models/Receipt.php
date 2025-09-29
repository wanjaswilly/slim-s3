<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{

    protected $table = 'receipts';
    protected $fillable = [
        'sale_id',
        'receipt_number',
        'type',
        'status',
        'issued_at',
        'pdf_url',
        'html_content',
        'data',
        'metadata'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'data' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    // Scopes
    public function scopeIssued($query)
    {
        return $query->whereNotNull('issued_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('issued_at', '>=', Carbon::now()->subDays($days));
    }

    // Methods
    public function generateReceiptNumber()
    {
        if (!$this->receipt_number) {
            $shop = $this->sale->shop;
            $prefix = strtoupper(substr($shop->name, 0, 3));
            $date = Carbon::now()->format('Ymd');
            $sequence = static::where('shop_id', $shop->id)
                ->whereDate('issued_at', Carbon::today())
                ->count() + 1;

            $this->receipt_number = "{$prefix}-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        }

        return $this->receipt_number;
    }

    public function issue()
    {
        $this->generateReceiptNumber();
        $this->issued_at = Carbon::now();
        $this->status = 'issued';
        
        // Generate receipt data
        $this->data = $this->generateReceiptData();
        
        return $this->save();
    }

    private function generateReceiptData()
    {
        $sale = $this->sale;
        $shop = $sale->shop;
        $customer = $sale->customer;

        return [
            'shop' => [
                'name' => $shop->name,
                'address' => $shop->address,
                'phone' => $shop->phone,
                'email' => $shop->email,
                'tax_number' => $shop->tax_number
            ],
            'customer' => [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email
            ],
            'sale' => [
                'sale_number' => $sale->sale_number,
                'date' => $sale->sale_date->format('Y-m-d H:i:s'),
                'items' => $sale->items->map(function ($item) {
                    return [
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'line_total' => $item->line_total
                    ];
                })->toArray(),
                'subtotal' => $sale->subtotal,
                'tax_amount' => $sale->tax_amount,
                'discount_amount' => $sale->discount_amount,
                'total_amount' => $sale->total_amount,
                'payment_method' => $sale->payment_method
            ],
            'receipt' => [
                'number' => $this->receipt_number,
                'issued_at' => $this->issued_at->format('Y-m-d H:i:s'),
                'qr_code_data' => $this->generateQrCodeData()
            ]
        ];
    }

    private function generateQrCodeData()
    {
        return [
            'receipt_number' => $this->receipt_number,
            'sale_number' => $this->sale->sale_number,
            'shop_id' => $this->sale->shop_id,
            'total_amount' => $this->sale->total_amount,
            'date' => $this->issued_at->format('Y-m-d')
        ];
    }

    public function isIssued()
    {
        return $this->status === 'issued' && $this->issued_at;
    }

    public function canBeVoided()
    {
        return $this->isIssued() && $this->issued_at->diffInHours(Carbon::now()) <= 24; // Within 24 hours
    }

    public function void($reason = '')
    {
        if ($this->canBeVoided()) {
            $this->status = 'voided';
            $this->metadata = array_merge($this->metadata ?? [], [
                'voided_at' => Carbon::now()->toISOString(),
                'void_reason' => $reason
            ]);
            return $this->save();
        }

        return false;
    }

    public function resend()
    {
        if ($this->isIssued() && $this->sale->customer->email) {
            // Logic to resend receipt via email
            return true;
        }

        return false;
    }

    public function getFormattedReceipt()
    {
        $data = $this->data;
        
        $formatted = [
            'header' => [
                'Shop' => $data['shop']['name'],
                'Address' => $data['shop']['address'],
                'Phone' => $data['shop']['phone']
            ],
            'customer' => [
                'Name' => $data['customer']['name'],
                'Phone' => $data['customer']['phone']
            ],
            'sale' => [
                'Receipt No' => $this->receipt_number,
                'Sale No' => $data['sale']['sale_number'],
                'Date' => $data['sale']['date']
            ],
            'items' => $data['sale']['items'],
            'totals' => [
                'Subtotal' => $data['sale']['subtotal'],
                'Tax' => $data['sale']['tax_amount'],
                'Discount' => $data['sale']['discount_amount'],
                'Total' => $data['sale']['total_amount']
            ],
            'payment' => [
                'Method' => $data['sale']['payment_method']
            ]
        ];

        return $formatted;
    }
}