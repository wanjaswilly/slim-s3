<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $table = 'saleitems';
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_amount',
        'line_total',
        'line_tax',
        'line_total_with_tax',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'line_tax' => 'decimal:2',
        'line_total_with_tax' => 'decimal:2',
        'metadata' => 'array'
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Scopes
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeHighValue($query, $threshold = 1000)
    {
        return $query->where('line_total', '>=', $threshold);
    }

    // Methods
    public function calculateLineTotal()
    {
        $lineTotal = $this->quantity * $this->unit_price;
        $lineTotalAfterDiscount = $lineTotal - $this->discount_amount;
        $lineTax = $lineTotalAfterDiscount * ($this->tax_rate / 100);
        
        $this->line_total = $lineTotalAfterDiscount;
        $this->line_tax = $lineTax;
        $this->line_total_with_tax = $lineTotalAfterDiscount + $lineTax;
        
        return $this;
    }

    public function getProfit()
    {
        if (!$this->product) {
            return 0;
        }

        $cost = $this->product->cost_price * $this->quantity;
        return $this->line_total - $cost;
    }

    public function getProfitMargin()
    {
        if ($this->line_total == 0) {
            return 0;
        }

        return ($this->getProfit() / $this->line_total) * 100;
    }

    public function updateInventory()
    {
        if ($this->product && $this->product->track_quantity) {
            $inventory = $this->product->inventory;
            if ($inventory) {
                $inventory->sell($this->quantity);
            }
        }
    }

    public function restoreInventory()
    {
        if ($this->product && $this->product->track_quantity) {
            $inventory = $this->product->inventory;
            if ($inventory) {
                $inventory->restock($this->quantity);
            }
        }
    }

    public function getTaxAmount()
    {
        return $this->line_tax;
    }

    public function getDiscountPercentage()
    {
        if ($this->unit_price * $this->quantity == 0) {
            return 0;
        }

        return ($this->discount_amount / ($this->unit_price * $this->quantity)) * 100;
    }

    public static function createFromProduct($saleId, $productId, $quantity, $unitPrice = null, $discount = 0)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        $unitPrice = $unitPrice ?? $product->selling_price;

        $saleItem = new static([
            'sale_id' => $saleId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $product->tax_rate,
            'discount_amount' => $discount
        ]);

        $saleItem->calculateLineTotal()->save();
        return $saleItem;
    }
}