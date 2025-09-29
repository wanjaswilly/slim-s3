<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'productimages';

    protected $fillable = [
        'product_id',
        'shop_id',
        'image_url',
        'thumbnail_url',
        'large_url',
        'original_url',
        'file_name',
        'file_size',
        'mime_type',
        'dimensions',
        'alt_text',
        'caption',
        'sort_order',
        'is_primary',
        'is_visible',
        'color_variant',
        'platform_data',
        'metadata'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'dimensions' => 'array',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'is_visible' => 'boolean',
        'platform_data' => 'array',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'sort_order' => 0,
        'is_primary' => false,
        'is_visible' => true
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

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_images')
                    ->withTimestamps();
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    public function scopeLargeFiles($query, $size = 5242880) // 5MB
    {
        return $query->where('file_size', '>', $size);
    }

    public function scopeByColor($query, $color)
    {
        return $query->where('color_variant', $color);
    }

    // Methods
    public function getFileSizeFormatted()
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function getDimensionsFormatted()
    {
        $dimensions = $this->dimensions;
        
        if (!$dimensions || !isset($dimensions['width']) || !isset($dimensions['height'])) {
            return 'N/A';
        }

        return $dimensions['width'] . '×' . $dimensions['height'];
    }

    public function getAspectRatio()
    {
        $dimensions = $this->dimensions;
        
        if (!$dimensions || !isset($dimensions['width']) || !isset($dimensions['height']) || $dimensions['height'] == 0) {
            return null;
        }

        return $dimensions['width'] / $dimensions['height'];
    }

    public function makePrimary()
    {
        // Remove primary status from other images of the same product
        $this->product->images()->where('id', '!=', $this->id)->update(['is_primary' => false]);
        
        // Set this image as primary
        $this->update(['is_primary' => true]);
        
        return $this;
    }

    public function hide()
    {
        $this->update(['is_visible' => false]);
        return $this;
    }

    public function show()
    {
        $this->update(['is_visible' => true]);
        return $this;
    }

    public function getOptimizedUrl($width = null, $height = null, $quality = 80)
    {
        if (!$this->image_url) {
            return null;
        }

        // In production, this would use a CDN or image processing service
        // For now, return the appropriate URL based on requested size
        if ($width && $height && $this->large_url) {
            return $this->large_url . "?w={$width}&h={$height}&q={$quality}";
        } elseif ($width && $height && $this->thumbnail_url) {
            return $this->thumbnail_url;
        }

        return $this->image_url;
    }

    public function getImageUrls()
    {
        return [
            'original' => $this->original_url ?: $this->image_url,
            'large' => $this->large_url ?: $this->image_url,
            'medium' => $this->image_url,
            'thumbnail' => $this->thumbnail_url ?: $this->image_url,
            'optimized' => $this->getOptimizedUrl(800, 600)
        ];
    }

    public function isPortrait()
    {
        $aspectRatio = $this->getAspectRatio();
        return $aspectRatio && $aspectRatio < 1;
    }

    public function isLandscape()
    {
        $aspectRatio = $this->getAspectRatio();
        return $aspectRatio && $aspectRatio > 1;
    }

    public function isSquare()
    {
        $aspectRatio = $this->getAspectRatio();
        return $aspectRatio && abs($aspectRatio - 1) < 0.1;
    }

    public function meetsSizeRequirements($minWidth = 500, $minHeight = 500)
    {
        $dimensions = $this->dimensions;
        
        if (!$dimensions || !isset($dimensions['width']) || !isset($dimensions['height'])) {
            return false;
        }

        return $dimensions['width'] >= $minWidth && $dimensions['height'] >= $minHeight;
    }

    public function generateAltText()
    {
        if ($this->alt_text) {
            return $this->alt_text;
        }

        $product = $this->product;
        $color = $this->color_variant ? " in {$this->color_variant}" : "";
        
        return "{$product->name}{$color} - {$product->shop->name}";
    }

    public static function createFromUpload($productId, $imageData)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        $isFirstImage = !$product->images()->exists();

        $image = new static([
            'product_id' => $productId,
            'shop_id' => $product->shop_id,
            'image_url' => $imageData['url'] ?? null,
            'thumbnail_url' => $imageData['thumbnail_url'] ?? null,
            'large_url' => $imageData['large_url'] ?? null,
            'original_url' => $imageData['original_url'] ?? null,
            'file_name' => $imageData['name'] ?? null,
            'file_size' => $imageData['size'] ?? 0,
            'mime_type' => $imageData['mime_type'] ?? null,
            'dimensions' => $imageData['dimensions'] ?? null,
            'alt_text' => $imageData['alt_text'] ?? null,
            'caption' => $imageData['caption'] ?? null,
            'color_variant' => $imageData['color_variant'] ?? null,
            'sort_order' => $product->images()->count(),
            'is_primary' => $isFirstImage
        ]);

        $image->save();

        // Generate alt text if not provided
        if (!$image->alt_text) {
            $image->update(['alt_text' => $image->generateAltText()]);
        }

        return $image;
    }

    public function updatePlatformData($platform, $data)
    {
        $platformData = $this->platform_data ?? [];
        $platformData[$platform] = $data;
        $this->platform_data = $platformData;
        $this->save();
    }

    public function getPlatformData($platform)
    {
        $platformData = $this->platform_data ?? [];
        return $platformData[$platform] ?? null;
    }

    public function associateWithVariant($variantId)
    {
        return $this->variants()->attach($variantId);
    }

    public function dissociateFromVariant($variantId)
    {
        return $this->variants()->detach($variantId);
    }
}