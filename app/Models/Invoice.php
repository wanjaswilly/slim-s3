<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'shop_id',
        'subscription_id',
        'plan_id',
        'stripe_invoice_id',
        'stripe_payment_intent_id',
        'number',
        'status',
        'due_date',
        'paid_date',
        'finalized_at',
        'period_start',
        'period_end',
        'subtotal',
        'tax_amount',
        'total',
        'amount_due',
        'amount_paid',
        'amount_remaining',
        'currency',
        'invoice_pdf_url',
        'hosted_invoice_url',
        'billing_reason',
        'attempt_count',
        'next_payment_attempt',
        'metadata',
        'line_items'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'paid_date' => 'datetime',
        'finalized_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_remaining' => 'decimal:2',
        'attempt_count' => 'integer',
        'next_payment_attempt' => 'datetime',
        'metadata' => 'array',
        'line_items' => 'array'
    ];

    protected $attributes = [
        'status' => 'draft',
        'currency' => 'KES',
        'attempt_count' => 0,
        'subtotal' => 0,
        'tax_amount' => 0,
        'total' => 0,
        'amount_due' => 0,
        'amount_paid' => 0,
        'amount_remaining' => 0
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeVoid($query)
    {
        return $query->where('status', 'void');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::now())
                    ->whereIn('status', ['open', 'draft']);
    }

    public function scopeThisMonth($query)
    {
        return $query->where('due_date', '>=', Carbon::now()->startOfMonth())
                    ->where('due_date', '<=', Carbon::now()->endOfMonth());
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Methods
    public function generateInvoiceNumber()
    {
        if (!$this->number) {
            $prefix = 'INV';
            $date = Carbon::now()->format('Ym');
            $sequence = static::where('shop_id', $this->shop_id)
                ->where('number', 'like', "{$prefix}-{$date}-%")
                ->count() + 1;

            $this->number = "{$prefix}-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        }

        return $this->number;
    }

    public function markAsPaid($paidDate = null, $paymentMethod = null)
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => $paidDate ?? Carbon::now(),
            'amount_paid' => $this->total,
            'amount_remaining' => 0
        ]);

        // Record payment
        if ($paymentMethod) {
            $this->payments()->create([
                'shop_id' => $this->shop_id,
                'sale_id' => null,
                'customer_id' => null,
                'payment_method' => $paymentMethod,
                'amount' => $this->total,
                'currency' => $this->currency,
                'status' => 'completed',
                'payment_date' => Carbon::now()
            ]);
        }
    }

    public function markAsOpen()
    {
        $this->update([
            'status' => 'open',
            'amount_due' => $this->total,
            'amount_remaining' => $this->total
        ]);
    }

    public function markAsVoid($reason = '')
    {
        $this->update([
            'status' => 'void',
            'amount_due' => 0,
            'amount_remaining' => 0
        ]);

        $this->metadata = array_merge($this->metadata ?? [], [
            'voided_at' => Carbon::now()->toISOString(),
            'void_reason' => $reason
        ]);

        $this->save();
    }

    public function finalize()
    {
        if ($this->status === 'draft') {
            $this->update([
                'status' => 'open',
                'finalized_at' => Carbon::now(),
                'amount_due' => $this->total,
                'amount_remaining' => $this->total
            ]);

            $this->generateInvoiceNumber();
        }
    }

    public function addLineItem($description, $amount, $quantity = 1, $taxable = true)
    {
        $lineItems = $this->line_items ?? [];
        
        $lineItems[] = [
            'description' => $description,
            'amount' => $amount,
            'quantity' => $quantity,
            'taxable' => $taxable,
            'line_total' => $amount * $quantity
        ];

        $this->line_items = $lineItems;
        $this->recalculateTotals();
        
        return $this;
    }

    public function recalculateTotals()
    {
        $lineItems = $this->line_items ?? [];
        
        $subtotal = 0;
        foreach ($lineItems as $item) {
            $subtotal += $item['line_total'] ?? 0;
        }

        $taxAmount = $subtotal * 0.16; // Assuming 16% VAT for Kenya

        $this->subtotal = $subtotal;
        $this->tax_amount = $taxAmount;
        $this->total = $subtotal + $taxAmount;
        $this->amount_due = $this->total - $this->amount_paid;
        $this->amount_remaining = $this->amount_due;

        $this->save();
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && $this->status === 'open';
    }

    public function getOverdueDays()
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(Carbon::now());
    }

    public function canBePaid()
    {
        return in_array($this->status, ['open', 'draft']) && $this->amount_due > 0;
    }

    public function canBeVoided()
    {
        return in_array($this->status, ['draft', 'open']) && $this->amount_paid == 0;
    }

    public function recordPayment($amount, $paymentMethod, $transactionId = null)
    {
        $this->amount_paid += $amount;
        $this->amount_remaining = max(0, $this->amount_due - $this->amount_paid);

        if ($this->amount_remaining == 0) {
            $this->status = 'paid';
            $this->paid_date = Carbon::now();
        }

        $this->save();

        // Record payment transaction
        $this->payments()->create([
            'shop_id' => $this->shop_id,
            'sale_id' => null,
            'customer_id' => null,
            'payment_method' => $paymentMethod,
            'processor' => 'stripe',
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $this->currency,
            'status' => 'completed',
            'payment_date' => Carbon::now()
        ]);
    }

    public function getLineItemsTotal()
    {
        $lineItems = $this->line_items ?? [];
        $total = 0;
        
        foreach ($lineItems as $item) {
            $total += $item['line_total'] ?? 0;
        }
        
        return $total;
    }

    public function getFormattedStatus()
    {
        $statusMap = [
            'draft' => 'Draft',
            'open' => 'Unpaid',
            'paid' => 'Paid',
            'void' => 'Void',
            'uncollectible' => 'Uncollectible'
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    public static function createFromSubscription(Subscription $subscription, $billingReason = 'subscription_cycle')
    {
        $plan = $subscription->plan;
        $shop = $subscription->shop;

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();
        $dueDate = Carbon::now()->addDays(7);

        $invoice = new static([
            'shop_id' => $shop->id,
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'status' => 'draft',
            'due_date' => $dueDate,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'currency' => $plan->currency,
            'billing_reason' => $billingReason
        ]);

        $invoice->addLineItem(
            $plan->name . ' Subscription',
            $plan->price,
            1,
            true
        );

        $invoice->finalize();
        return $invoice;
    }

    public function sendToCustomer()
    {
        // This would integrate with email service to send invoice to customer
        // For now, just mark as sent
        $this->metadata = array_merge($this->metadata ?? [], [
            'sent_at' => Carbon::now()->toISOString(),
            'sent_to' => $this->shop->email
        ]);
        $this->save();

        return true;
    }
}