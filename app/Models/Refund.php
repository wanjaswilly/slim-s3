<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $table = 'refunds';

    protected $fillable = [
        'payment_id',
        'sale_id',
        'shop_id',
        'customer_id',
        'refund_number',
        'amount',
        'currency',
        'reason',
        'status',
        'refunded_at',
        'processed_at',
        'failure_reason',
        'processor_reference',
        'processor_response',
        'refund_method',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_at' => 'datetime',
        'processed_at' => 'datetime',
        'processor_response' => 'array',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'currency' => 'KES',
        'status' => 'pending',
        'refund_method' => 'original'
    ];

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

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

    public function refundItems()
    {
        return $this->hasMany(RefundItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed($query)
    {
        return $this->where('status', 'failed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->startOfMonth());
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeByPayment($query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeHighValue($query, $threshold = 1000)
    {
        return $query->where('amount', '>=', $threshold);
    }

    // Methods
    public function generateRefundNumber()
    {
        if (!$this->refund_number) {
            $prefix = 'REF';
            $date = Carbon::now()->format('Ym');
            $sequence = static::where('shop_id', $this->shop_id)
                ->where('refund_number', 'like', "{$prefix}-{$date}-%")
                ->count() + 1;

            $this->refund_number = "{$prefix}-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        }

        return $this->refund_number;
    }

    public function markAsProcessed($processorReference = null, $processedAt = null)
    {
        $this->update([
            'status' => 'processed',
            'processor_reference' => $processorReference,
            'processed_at' => $processedAt ?? Carbon::now(),
            'refunded_at' => $processedAt ?? Carbon::now()
        ]);

        // Update payment refund status
        if ($this->payment) {
            $refundedAmount = $this->payment->refunds()->where('status', 'processed')->sum('amount');
            
            if ($refundedAmount >= $this->payment->amount) {
                $this->payment->update(['status' => 'refunded']);
            } else {
                $this->payment->update(['status' => 'partially_refunded']);
            }
        }

        // Update sale status if fully refunded
        if ($this->sale) {
            $totalRefunded = $this->sale->payments()
                ->whereHas('refunds', function ($query) {
                    $query->where('status', 'processed');
                })
                ->get()
                ->sum(function ($payment) {
                    return $payment->refunds()->where('status', 'processed')->sum('amount');
                });

            if ($totalRefunded >= $this->sale->total_amount) {
                $this->sale->update(['status' => 'refunded']);
            }
        }
    }

    public function markAsFailed($failureReason, $processorResponse = null)
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $failureReason,
            'processor_response' => $processorResponse
        ]);
    }

    public function markAsCancelled($reason = '')
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $this->notes . "\nCancelled: " . $reason
        ]);
    }

    public function isProcessed()
    {
        return $this->status === 'processed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function canBeProcessed()
    {
        return $this->isPending() && 
               $this->payment && 
               $this->payment->canBeRefunded() &&
               $this->amount <= $this->payment->getRefundableAmount();
    }

    public function canBeCancelled()
    {
        return $this->isPending();
    }

    public function getRefundableAmount()
    {
        if (!$this->payment) {
            return 0;
        }

        $alreadyRefunded = $this->payment->refunds()
            ->where('status', 'processed')
            ->sum('amount');

        return $this->payment->amount - $alreadyRefunded;
    }

    public function processRefund($request, $user, $processorData = null)
    {
        if (!$this->canBeProcessed()) {
            return false;
        }

        try {
            // Simulate processor integration
            // In production, this would integrate with Stripe, MPesa, etc.
            $processorResponse = [
                'id' => 'ref_' . uniqid(),
                'status' => 'succeeded',
                'amount' => $this->amount,
                'currency' => $this->currency,
                'created' => time()
            ];

            $this->markAsProcessed(
                $processorResponse['id'],
                Carbon::now()
            );

            $this->processor_response = $processorResponse;
            $this->save();

            // Restore inventory if this is a product refund
            $this->restoreInventory();

            // Log activity
            UserActivity::log(
                $request,
                $user->id(),
                'refund_processed',
                "Processed refund {$this->refund_number} for {$this->amount} {$this->currency}",
                $this->shop_id,
                $this
            );

            return true;

        } catch (\Exception $e) {
            $this->markAsFailed($e->getMessage());
            return false;
        }
    }

    public function restoreInventory()
    {
        if (!$this->sale) {
            return;
        }

        foreach ($this->refundItems as $refundItem) {
            $saleItem = $refundItem->saleItem;
            
            if ($saleItem && $saleItem->product) {
                $inventory = $saleItem->product->inventory;
                
                if ($inventory) {
                    $inventory->restock($refundItem->quantity_refunded);
                }
            }
        }
    }

    public function addRefundItem($saleItemId, $quantity, $unitPrice = null, $reason = '')
    {
        $saleItem = SaleItem::find($saleItemId);
        
        if (!$saleItem || $saleItem->sale_id !== $this->sale_id) {
            return null;
        }

        $maxRefundable = $saleItem->quantity - $saleItem->refundedItems()->sum('quantity_refunded');
        $quantityToRefund = min($quantity, $maxRefundable);

        if ($quantityToRefund <= 0) {
            return null;
        }

        $unitPrice = $unitPrice ?? $saleItem->unit_price;
        $refundAmount = $quantityToRefund * $unitPrice;

        $refundItem = $this->refundItems()->create([
            'sale_item_id' => $saleItemId,
            'quantity_refunded' => $quantityToRefund,
            'unit_price' => $unitPrice,
            'refund_amount' => $refundAmount,
            'reason' => $reason
        ]);

        // Update refund amount
        $this->increment('amount', $refundAmount);
        
        return $refundItem;
    }

    public function getRefundItemsSummary()
    {
        return $this->refundItems->map(function ($item) {
            return [
                'product_name' => $item->saleItem->product->name,
                'quantity' => $item->quantity_refunded,
                'unit_price' => $item->unit_price,
                'refund_amount' => $item->refund_amount,
                'reason' => $item->reason
            ];
        });
    }

    public function getProcessorResponse()
    {
        return $this->processor_response ? json_decode($this->processor_response, true) : [];
    }

    public static function createFullRefund(Payment $payment, $reason = 'customer_request')
    {
        $refundableAmount = $payment->getRefundableAmount();
        
        if ($refundableAmount <= 0) {
            return null;
        }

        $refund = new static([
            'payment_id' => $payment->id,
            'sale_id' => $payment->sale_id,
            'shop_id' => $payment->shop_id,
            'customer_id' => $payment->customer_id,
            'amount' => $refundableAmount,
            'currency' => $payment->currency,
            'reason' => $reason,
            'status' => 'pending',
            'refund_method' => 'original'
        ]);

        $refund->generateRefundNumber();
        $refund->save();

        // Add refund items for all sale items if it's a full refund
        if ($payment->sale) {
            foreach ($payment->sale->items as $saleItem) {
                $refund->addRefundItem(
                    $saleItem->id,
                    $saleItem->quantity,
                    $saleItem->unit_price,
                    $reason
                );
            }
        }

        return $refund;
    }

    public static function createPartialRefund(Payment $payment, $amount, $reason = 'partial_refund')
    {
        $refundableAmount = $payment->getRefundableAmount();
        
        if ($amount <= 0 || $amount > $refundableAmount) {
            return null;
        }

        $refund = new static([
            'payment_id' => $payment->id,
            'sale_id' => $payment->sale_id,
            'shop_id' => $payment->shop_id,
            'customer_id' => $payment->customer_id,
            'amount' => $amount,
            'currency' => $payment->currency,
            'reason' => $reason,
            'status' => 'pending',
            'refund_method' => 'original'
        ]);

        $refund->generateRefundNumber();
        $refund->save();

        return $refund;
    }

    public function getFormattedStatus()
    {
        $statusMap = [
            'pending' => 'Pending',
            'processed' => 'Processed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled'
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    public function notifyCustomer()
    {
        // This would integrate with notification system
        // For now, just log the action
        // \Log::info("Refund notification sent to customer for refund {$this->refund_number}");
        
        $this->metadata = array_merge($this->metadata ?? [], [
            'customer_notified_at' => Carbon::now()->toISOString()
        ]);
        $this->save();

        return true;
    }
}