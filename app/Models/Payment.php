<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $fillable = [
        'shop_id',
        'sale_id',
        'customer_id',
        'payment_method',
        'processor',
        'transaction_id',
        'reference',
        'amount',
        'currency',
        'status',
        'payment_date',
        'processed_at',
        'failure_reason',
        'processor_response',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'processed_at' => 'datetime',
        'processor_response' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }


    /**
     * Get refunds for this payment
     */
    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Get total refunded amount
     */
    public function getRefundedAmount()
    {
        return $this->refunds()->where('status', 'processed')->sum('amount');
    }

    /**
     * Check if payment can be refunded
     */
    public function canBeRefunded()
    {
        return $this->isSuccessful() && $this->getRefundedAmount() < $this->amount;
    }

    /**
     * Get remaining refundable amount
     */
    public function getRefundableAmount()
    {
        return $this->amount - $this->getRefundedAmount();
    }

    /**
     * Check if payment is fully refunded
     */
    public function isFullyRefunded()
    {
        return $this->getRefundedAmount() >= $this->amount;
    }

    /**
     * Check if payment is partially refunded
     */
    public function isPartiallyRefunded()
    {
        $refunded = $this->getRefundedAmount();
        return $refunded > 0 && $refunded < $this->amount;
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeThisMonth($query)
    {
        return $query->where('payment_date', '>=', Carbon::now()->startOfMonth());
    }

    // Methods
    public function markAsCompleted($processorResponse = null)
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => Carbon::now(),
            'processor_response' => $processorResponse
        ]);

        // Update sale payment status
        if ($this->sale) {
            $this->sale->markAsPaid($this->payment_method);
        }
    }

    public function markAsFailed($reason, $processorResponse = null)
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'processor_response' => $processorResponse
        ]);
    }

    public function isSuccessful()
    {
        return $this->status === 'completed';
    }

    public function isMpesa()
    {
        return $this->payment_method === 'mpesa';
    }

    public function getProcessorResponse()
    {
        return $this->processor_response ? json_decode($this->processor_response, true) : [];
    }

    public function createRefund($amount, $reason = '')
    {
        return $this->refunds()->create([
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending'
        ]);
    }
}
