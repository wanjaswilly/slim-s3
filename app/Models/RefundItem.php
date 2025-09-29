<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundItem extends Model
{
    protected $table = 'refunditems';
    
    protected $fillable = [
        'refund_id',
        'sale_item_id',
        'quantity_refunded',
        'unit_price',
        'refund_amount',
        'reason',
        'metadata'
    ];

    protected $casts = [
        'quantity_refunded' => 'integer',
        'unit_price' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'metadata' => 'array'
    ];

    // Relationships
    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    // Methods
    public function getProduct()
    {
        return $this->saleItem->product;
    }

    public function calculateRefundAmount()
    {
        $this->refund_amount = $this->quantity_refunded * $this->unit_price;
        return $this;
    }
}