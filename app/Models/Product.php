<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'shop_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'short_description',
        'specifications',
        'brand',
        'model',
        'unit',
        'cost_price',
        'selling_price',
        'compare_price',
        'profit_margin',
        'tax_rate',
        'weight',
        'dimensions',
        'is_active',
        'is_featured',
        'is_digital',
        'is_virtual',
        'track_quantity',
        'allow_backorders',
        'low_stock_threshold',
        'meta_title',
        'meta_description',
        'tags',
        'attributes',
        'variants',
        'seo_data',
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
        'is_featured' => 'boolean',
        'is_digital' => 'boolean',
        'is_virtual' => 'boolean',
        'track_quantity' => 'boolean',
        'allow_backorders' => 'boolean',
        'specifications' => 'array',
        'tags' => 'array',
        'attributes' => 'array',
        'variants' => 'array',
        'seo_data' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'sale_items')
                    ->withPivot(['quantity', 'unit_price', 'line_total'])
                    ->withTimestamps();
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_products');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->where('quantity_on_hand', '>', 0);
        });
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->whereRaw('quantity_on_hand <= low_stock_threshold');
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    // Methods
    public function getStockQuantity()
    {
        return $this->inventory ? $this->inventory->quantity_on_hand : 0;
    }

    public function isInStock()
    {
        return $this->getStockQuantity() > 0;
    }

    public function isLowStock()
    {
        return $this->track_quantity && 
               $this->getStockQuantity() <= $this->low_stock_threshold;
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

    public function updateStock($quantity)
    {
        if (!$this->inventory) {
            $this->inventory()->create([
                'quantity_on_hand' => $quantity,
                'low_stock_threshold' => $this->low_stock_threshold
            ]);
        } else {
            $this->inventory->update(['quantity_on_hand' => $quantity]);
        }
    }

    public function getPrimaryImage()
    {
        return $this->images()->orderBy('is_primary', 'desc')->first();
    }

    public function getGalleryImages()
    {
        return $this->images()->where('is_primary', false)->get();
    }
}