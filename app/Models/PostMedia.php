<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostMedia extends Model
{
    protected $table = 'postmedias';

    protected $fillable = [
        'post_id',
        'shop_id',
        'media_type',
        'file_url',
        'thumbnail_url',
        'file_name',
        'file_size',
        'mime_type',
        'duration',
        'dimensions',
        'alt_text',
        'caption',
        'sort_order',
        'is_primary',
        'metadata',
        'platform_data'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'duration' => 'integer',
        'dimensions' => 'array',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'metadata' => 'array',
        'platform_data' => 'array'
    ];

    protected $attributes = [
        'media_type' => 'image',
        'sort_order' => 0,
        'is_primary' => false
    ];

    // Relationships
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function insights()
    {
        return $this->hasMany(PostInsight::class, 'media_id');
    }

    // Scopes
    public function scopeImages($query)
    {
        return $query->where('media_type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('media_type', 'video');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('media_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    public function scopeLargeFiles($query, $size = 10485760) // 10MB
    {
        return $query->where('file_size', '>', $size);
    }

    // Methods
    public function getFileSizeFormatted()
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
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

    public function isImage()
    {
        return $this->media_type === 'image';
    }

    public function isVideo()
    {
        return $this->media_type === 'video';
    }

    public function isAudio()
    {
        return $this->media_type === 'audio';
    }

    public function isDocument()
    {
        return $this->media_type === 'document';
    }

    public function getDurationFormatted()
    {
        if (!$this->duration) {
            return null;
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getPlatformData($platform)
    {
        $platformData = $this->platform_data ?? [];
        return $platformData[$platform] ?? null;
    }

    public function setPlatformData($platform, $data)
    {
        $platformData = $this->platform_data ?? [];
        $platformData[$platform] = $data;
        $this->platform_data = $platformData;
        $this->save();
    }

    public function getOptimizedUrl($width = null, $height = null)
    {
        if (!$this->file_url) {
            return null;
        }

        // In production, this would use a CDN or image processing service
        // For now, return the original URL
        return $this->file_url;
    }

    public function makePrimary()
    {
        // Remove primary status from other media in the same post
        $this->post->media()->update(['is_primary' => false]);
        
        // Set this media as primary
        $this->update(['is_primary' => true]);
        
        return $this;
    }

    public static function createFromUpload($postId, $fileData, $mediaType = 'image')
    {
        $post = Post::find($postId);
        if (!$post) {
            return null;
        }

        $media = new static([
            'post_id' => $postId,
            'shop_id' => $post->shop_id,
            'media_type' => $mediaType,
            'file_url' => $fileData['url'] ?? null,
            'file_name' => $fileData['name'] ?? null,
            'file_size' => $fileData['size'] ?? 0,
            'mime_type' => $fileData['mime_type'] ?? null,
            'dimensions' => $fileData['dimensions'] ?? null,
            'alt_text' => $fileData['alt_text'] ?? null,
            'caption' => $fileData['caption'] ?? null
        ]);

        $media->save();
        return $media;
    }

    public function getMediaTypeIcon()
    {
        $icons = [
            'image' => '🖼️',
            'video' => '🎥',
            'audio' => '🎵',
            'document' => '📄',
            'gif' => '🎬',
            'carousel' => '🔄'
        ];

        return $icons[$this->media_type] ?? '📎';
    }
}