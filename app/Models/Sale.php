<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'shop_id',
        'seller_id',
        'customer_id',
        'sale_number',
        'status',
        'payment_status',
        'payment_method',
        'channel',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'currency',
        'exchange_rate',
        'customer_notes',
        'internal_notes',
        'sale_date',
        'due_date',
        'paid_at',
        'cancelled_at',
        'refunded_at',
        'metadata'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'sale_date' => 'datetime',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function shipping()
    {
        return $this->hasOne(Shipping::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::now())
                    ->where('payment_status', '!=', 'paid');
    }

    public function scopeThisMonth($query)
    {
        return $query->where('sale_date', '>=', Carbon::now()->startOfMonth());
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    // Methods
    public function markAsPaid($paymentMethod = null, $paidAt = null)
    {
        return $this->update([
            'payment_status' => 'paid',
            'payment_method' => $paymentMethod ?? $this->payment_method,
            'paid_at' => $paidAt ?? Carbon::now()
        ]);
    }

    public function markAsCompleted()
    {
        return $this->update(['status' => 'completed']);
    }

    public function cancel($reason = null)
    {
        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'internal_notes' => $this->internal_notes . "\nCancelled: " . ($reason ?? 'No reason provided')
        ]);
    }

    public function refund($amount = null)
    {
        $refundAmount = $amount ?? $this->total_amount;
        
        return $this->update([
            'payment_status' => 'refunded',
            'refunded_at' => Carbon::now(),
            'total_amount' => $this->total_amount - $refundAmount
        ]);
    }

    public function addItem($productId, $quantity, $unitPrice, $taxRate = null)
    {
        return $this->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate ?? $this->shop->tax_rate,
            'line_total' => $quantity * $unitPrice
        ]);
    }

    public function recalculateTotals()
    {
        $subtotal = $this->items->sum('line_total');
        $taxAmount = $this->items->sum(function ($item) {
            return $item->line_total * ($item->tax_rate / 100);
        });
        
        $total = $subtotal + $taxAmount + $this->shipping_amount - $this->discount_amount;

        return $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $total
        ]);
    }

    public function getPaymentBalance()
    {
        $paidAmount = $this->payments()->where('status', 'completed')->sum('amount');
        return $this->total_amount - $paidAmount;
    }

    public function isFullyPaid()
    {
        return $this->getPaymentBalance() <= 0;
    }

    public function generateSaleNumber()
    {
        if (!$this->sale_number) {
            $shopPrefix = strtoupper(substr($this->shop->name, 0, 3));
            $this->sale_number = $shopPrefix . '-' . date('Ymd') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
            $this->save();
        }
        return $this->sale_number;
    }
}