<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'productvariants';

    protected $fillable = [
        'product_id',
        'shop_id',
        'parent_id',
        'sku',
        'barcode',
        'name',
        'option1',
        'option2',
        'option3',
        'option_values',
        'cost_price',
        'selling_price',
        'compare_price',
        'profit_margin',
        'tax_rate',
        'weight',
        'weight_unit',
        'dimensions',
        'is_active',
        'is_default',
        'track_quantity',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'low_stock_threshold',
        'reorder_point',
        'reorder_quantity',
        'stock_status',
        'images_order',
        'metadata'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'track_quantity' => 'boolean',
        'quantity_on_hand' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_available' => 'integer',
        'low_stock_threshold' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'option_values' => 'array',
        'images_order' => 'array',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'is_active' => true,
        'is_default' => false,
        'track_quantity' => true,
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
        'quantity_available' => 0,
        'low_stock_threshold' => 5,
        'reorder_point' => 10,
        'reorder_quantity' => 25,
        'weight_unit' => 'kg',
        'stock_status' => 'out_of_stock'
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

    public function parent()
    {
        return $this->belongsTo(ProductVariant::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductVariant::class, 'parent_id');
    }

    public function images()
    {
        return $this->belongsToMany(ProductImage::class, 'product_variant_images')
                    ->withTimestamps();
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'variant_id');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'variant_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity_available', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_available', '<=', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity_available <= low_stock_threshold')
                    ->where('quantity_available', '>', 0);
    }

    public function scopeByOption($query, $option, $value)
    {
        return $query->where("option{$option}", $value);
    }

    public function scopeByOptions($query, array $options)
    {
        foreach ($options as $index => $value) {
            $query->where("option" . ($index + 1), $value);
        }
        return $query;
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithChildren($query)
    {
        return $query->whereHas('children');
    }

    public function scopeWithoutChildren($query)
    {
        return $query->whereDoesntHave('children');
    }

    // Methods
    public function getVariantName()
    {
        if ($this->name) {
            return $this->name;
        }

        $options = [];
        if ($this->option1) $options[] = $this->option1;
        if ($this->option2) $options[] = $this->option2;
        if ($this->option3) $options[] = $this->option3;

        return implode(' / ', $options) ?: 'Default';
    }

    public function getFullName()
    {
        $productName = $this->product->name;
        $variantName = $this->getVariantName();

        return $variantName !== 'Default' ? "{$productName} - {$variantName}" : $productName;
    }

    public function getOptions()
    {
        $options = [];
        if ($this->option1) $options['option1'] = $this->option1;
        if ($this->option2) $options['option2'] = $this->option2;
        if ($this->option3) $options['option3'] = $this->option3;

        return $options;
    }

    public function getOptionValues()
    {
        return $this->option_values ?? [];
    }

    public function setOption($position, $value, $displayName = null)
    {
        $this->{"option{$position}"} = $value;

        // Update option values array
        $optionValues = $this->option_values ?? [];
        $optionValues["option{$position}"] = [
            'value' => $value,
            'name' => $displayName ?: $value
        ];

        $this->option_values = $optionValues;
        return $this;
    }

    public function updateAvailableQuantity()
    {
        $this->quantity_available = max(0, $this->quantity_on_hand - $this->quantity_reserved);
        $this->updateStockStatus();
        $this->save();
    }

    public function updateStockStatus()
    {
        if ($this->quantity_available <= 0) {
            $this->stock_status = 'out_of_stock';
        } elseif ($this->quantity_available <= $this->low_stock_threshold) {
            $this->stock_status = 'low_stock';
        } else {
            $this->stock_status = 'in_stock';
        }
    }

    public function reserve($quantity)
    {
        if ($this->quantity_available >= $quantity) {
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

    public function sell($quantity)
    {
        if ($this->quantity_on_hand >= $quantity) {
            $this->quantity_on_hand -= $quantity;
            $this->updateAvailableQuantity();
            return true;
        }
        return false;
    }

    public function restock($quantity, $cost = null)
    {
        $this->quantity_on_hand += $quantity;
        
        if ($cost && $this->track_quantity) {
            $this->updateAverageCost($cost, $quantity);
        }
        
        $this->updateAvailableQuantity();
    }

    private function updateAverageCost($newCost, $newQuantity)
    {
        $totalValue = ($this->quantity_on_hand * $this->cost_price) + ($newQuantity * $newCost);
        $totalQuantity = $this->quantity_on_hand + $newQuantity;
        
        $this->cost_price = $totalQuantity > 0 ? $totalValue / $totalQuantity : 0;
    }

    public function getProfit()
    {
        return $this->selling_price - $this->cost_price;
    }

    public function getProfitPercentage()
    {
        if ($this->cost_price > 0) {
            return (($this->selling_price - $this->cost_price) / $this->cost_price) * 100;
        }
        return 0;
    }

    public function getDiscountPercentage()
    {
        if ($this->compare_price > $this->selling_price) {
            return (($this->compare_price - $this->selling_price) / $this->compare_price) * 100;
        }
        return 0;
    }

    public function isOnSale()
    {
        return $this->compare_price > $this->selling_price;
    }

    public function getWeightInGrams()
    {
        if ($this->weight_unit === 'g') {
            return $this->weight;
        } elseif ($this->weight_unit === 'kg') {
            return $this->weight * 1000;
        } elseif ($this->weight_unit === 'lb') {
            return $this->weight * 453.592;
        } elseif ($this->weight_unit === 'oz') {
            return $this->weight * 28.3495;
        }
        return $this->weight;
    }

    public function getVolume()
    {
        $dimensions = $this->dimensions;
        
        if (!$dimensions || !isset($dimensions['length']) || !isset($dimensions['width']) || !isset($dimensions['height'])) {
            return 0;
        }

        return $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
    }

    public function getPrimaryImage()
    {
        return $this->images()->wherePivot('is_primary', true)->first() 
            ?? $this->images()->orderByPivot('sort_order')->first()
            ?? $this->product->getPrimaryImage();
    }

    public function getGalleryImages()
    {
        $variantImages = $this->images()->orderByPivot('sort_order')->get();
        
        if ($variantImages->isNotEmpty()) {
            return $variantImages;
        }

        // Fall back to product images if no variant-specific images
        return $this->product->images()->where('is_visible', true)->ordered()->get();
    }

    public function addImage($imageId, $isPrimary = false, $sortOrder = null)
    {
        $existing = $this->images()->where('product_image_id', $imageId)->exists();
        
        if (!$existing) {
            $sortOrder = $sortOrder ?? $this->images()->count();
            
            $this->images()->attach($imageId, [
                'is_primary' => $isPrimary,
                'sort_order' => $sortOrder
            ]);

            // If this is set as primary, remove primary from other images
            if ($isPrimary) {
                $this->images()->wherePivot('product_image_id', '!=', $imageId)
                    ->update(['is_primary' => false]);
            }
        }

        return $this;
    }

    public function removeImage($imageId)
    {
        $this->images()->detach($imageId);
        return $this;
    }

    public function setPrimaryImage($imageId)
    {
        // Remove primary from all images
        $this->images()->update(['is_primary' => false]);
        
        // Set the specified image as primary
        $this->images()->updateExistingPivot($imageId, ['is_primary' => true]);
        
        return $this;
    }

    public function reorderImages($imageIds)
    {
        foreach ($imageIds as $index => $imageId) {
            $this->images()->updateExistingPivot($imageId, ['sort_order' => $index]);
        }
        
        $this->update(['images_order' => $imageIds]);
        return $this;
    }

    public function matchesOptions($options)
    {
        foreach ($options as $key => $value) {
            $optionField = "option{$key}";
            if ($this->$optionField !== $value) {
                return false;
            }
        }
        return true;
    }

    public function getCombinationKey()
    {
        $options = [
            $this->option1,
            $this->option2,
            $this->option3
        ];

        return implode('_', array_filter($options));
    }

    public static function findOrCreateVariant($productId, $options, $variantData = [])
    {
        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        // Look for existing variant with same options
        $query = static::where('product_id', $productId);
        
        foreach ($options as $index => $value) {
            $query->where("option" . ($index + 1), $value);
        }

        $variant = $query->first();

        if (!$variant) {
            $variantData = array_merge([
                'product_id' => $productId,
                'shop_id' => $product->shop_id,
                'sku' => $product->sku . '-' . uniqid(),
                'selling_price' => $product->selling_price,
                'cost_price' => $product->cost_price,
                'compare_price' => $product->compare_price,
                'weight' => $product->weight,
                'track_quantity' => $product->track_quantity
            ], $variantData);

            // Set options
            foreach ($options as $index => $value) {
                $variantData["option" . ($index + 1)] = $value;
            }

            $variant = static::create($variantData);
        }

        return $variant;
    }

    public function createChildVariant($options, $variantData = [])
    {
        $childData = array_merge([
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'parent_id' => $this->id,
            'sku' => $this->sku . '-' . uniqid(),
            'selling_price' => $this->selling_price,
            'cost_price' => $this->cost_price,
            'track_quantity' => $this->track_quantity
        ], $variantData, $options);

        $child = static::create($childData);
        return $child;
    }

    public function isChild()
    {
        return !is_null($this->parent_id);
    }

    public function isParent()
    {
        return $this->children()->exists();
    }

    public function getPriceDifference()
    {
        $basePrice = $this->parent ? $this->parent->selling_price : $this->product->selling_price;
        return $this->selling_price - $basePrice;
    }

    public function getPriceDifferenceFormatted()
    {
        $difference = $this->getPriceDifference();
        
        if ($difference > 0) {
            return '+' . number_format($difference, 2);
        } elseif ($difference < 0) {
            return number_format($difference, 2);
        } else {
            return '0.00';
        }
    }

    public function updateFromProduct()
    {
        if ($this->isChild()) {
            return $this;
        }

        $product = $this->product;

        $this->update([
            'cost_price' => $product->cost_price,
            'selling_price' => $product->selling_price,
            'compare_price' => $product->compare_price,
            'tax_rate' => $product->tax_rate,
            'weight' => $product->weight,
            'track_quantity' => $product->track_quantity,
            'low_stock_threshold' => $product->low_stock_threshold
        ]);

        return $this;
    }

    public function getInventoryValue()
    {
        return $this->quantity_on_hand * $this->cost_price;
    }

    public function needsReorder()
    {
        return $this->track_quantity && $this->quantity_on_hand <= $this->reorder_point;
    }

    public function getSalesVelocity($days = 30)
    {
        $salesCount = $this->saleItems()
            ->whereHas('sale', function ($query) use ($days) {
                $query->where('created_at', '>=', Carbon::now()->subDays($days))
                      ->where('status', 'completed');
            })
            ->sum('quantity');

        return $salesCount / $days; // Average daily sales
    }
}