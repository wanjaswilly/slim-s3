<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventorys';
    protected $fillable = [
        'product_id',
        'shop_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'quantity_ordered',
        'low_stock_threshold',
        'reorder_point',
        'reorder_quantity',
        'last_restocked_at',
        'last_sold_at',
        'stock_value',
        'average_cost',
        'metadata'
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_available' => 'integer',
        'quantity_ordered' => 'integer',
        'stock_value' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'last_restocked_at' => 'datetime',
        'last_sold_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }


    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity_on_hand <= low_stock_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_on_hand', '<=', 0);
    }

    public function scopeNeedsReorder($query)
    {
        return $query->whereRaw('quantity_on_hand <= reorder_point');
    }

    // Methods
    public function getAvailableQuantity()
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }

    public function updateAvailableQuantity()
    {
        $this->quantity_available = $this->getAvailableQuantity();
        $this->save();
    }

    public function reserve($quantity)
    {
        if ($this->getAvailableQuantity() >= $quantity) {
            $this->quantity_reserved += $quantity;
            $this->updateAvailableQuantity();
            return true;
        }
        return false;
    }

    public function release($quantity)
    {
        $this->quantity_reserved = max(0, $this->quantity_reserved - $quantity);
        $this->updateAvailableQuantity();
    }

    public function adjust($user, $newQuantity, $reason = 'manual_adjustment', $notes = null)
    {
        $oldQuantity = $this->quantity_on_hand;
        $difference = $newQuantity - $oldQuantity;

        $this->quantity_on_hand = $newQuantity;
        $this->updateAvailableQuantity();

        // Record adjustment
        $this->adjustments()->create([
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'difference' => $difference,
            'type' => $difference >= 0 ? 'in' : 'out',
            'reason' => $reason,
            'notes' => $notes,
            'adjusted_by' => $user->id()
        ]);

        // Update stock value
        $this->updateStockValue();
    }

    public function sell($quantity)
    {
        if ($this->getAvailableQuantity() >= $quantity) {
            $this->quantity_on_hand -= $quantity;
            $this->last_sold_at = Carbon::now();
            $this->updateAvailableQuantity();
            $this->updateStockValue();
            return true;
        }
        return false;
    }

    public function restock($quantity, $cost = null)
    {
        $this->quantity_on_hand += $quantity;
        $this->last_restocked_at = Carbon::now();
        
        if ($cost) {
            $this->updateAverageCost($cost, $quantity);
        }
        
        $this->updateAvailableQuantity();
        $this->updateStockValue();
    }

    private function updateAverageCost($newCost, $newQuantity)
    {
        $totalValue = ($this->quantity_on_hand * $this->average_cost) + ($newQuantity * $newCost);
        $totalQuantity = $this->quantity_on_hand + $newQuantity;
        
        $this->average_cost = $totalQuantity > 0 ? $totalValue / $totalQuantity : 0;
    }

    private function updateStockValue()
    {
        $this->stock_value = $this->quantity_on_hand * $this->average_cost;
        $this->save();
    }

    public function needsRestock()
    {
        return $this->quantity_on_hand <= $this->reorder_point;
    }
}