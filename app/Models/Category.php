<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    protected $table = 'categorys';

    protected $fillable = [
        'shop_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'image_url',
        'icon',
        'color',
        'is_active',
        'is_featured',
        'sort_order',
        'meta_title',
        'meta_description',
        'seo_data',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'seo_data' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
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

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithProducts($query)
    {
        return $query->whereHas('products', function ($q) {
            $q->active();
        });
    }

    // Methods
    public function getProductsCount()
    {
        return $this->products()->active()->count();
    }

    public function getFullPath()
    {
        $path = [];
        $category = $this;
        
        while ($category) {
            $path[] = $category->name;
            $category = $category->parent;
        }
        
        return implode(' > ', array_reverse($path));
    }

    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    public function hasChildren()
    {
        return $this->children()->exists();
    }

    public function getTree()
    {
        $categories = [];
        $this->buildTree($this, $categories);
        return $categories;
    }

    private function buildTree($category, &$result, $level = 0)
    {
        $result[] = [
            'category' => $category,
            'level' => $level
        ];
        
        foreach ($category->children as $child) {
            $this->buildTree($child, $result, $level + 1);
        }
    }
}