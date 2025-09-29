<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    protected $table = 'posts';
    protected $fillable = [
        'shop_id',
        'user_id',
        'type',
        'title',
        'content',
        'excerpt',
        'status',
        'scheduled_for',
        'published_at',
        'is_pinned',
        'metadata'
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
        'is_pinned' => 'boolean',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'post_products');
    }

    public function channels()
    {
        return $this->belongsToMany(Channel::class, 'post_channels')
                    ->withPivot(['external_id', 'status', 'posted_at', 'error_message'])
                    ->withTimestamps();
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class);
    }

    public function insights()
    {
        return $this->hasMany(PostInsight::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('scheduled_for', '>', Carbon::now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function publish()
    {
        return $this->update([
            'status' => 'published',
            'published_at' => Carbon::now()
        ]);
    }

    public function schedule($datetime)
    {
        return $this->update([
            'status' => 'scheduled',
            'scheduled_for' => $datetime
        ]);
    }

    public function isPublished()
    {
        return $this->status === 'published' && $this->published_at;
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled' && $this->scheduled_for > Carbon::now();
    }

    public function addProduct($productId)
    {
        return $this->products()->attach($productId);
    }

    public function removeProduct($productId)
    {
        return $this->products()->detach($productId);
    }

    public function publishToChannel($channelId)
    {
        $channel = $this->channels()->where('channel_id', $channelId)->first();
        
        if ($channel) {
            $channel->pivot->update([
                'status' => 'published',
                'posted_at' => Carbon::now()
            ]);
        }
    }

    public function getEngagementRate()
    {
        $insights = $this->insights->first();
        if (!$insights) return 0;

        $impressions = $insights->impressions ?: 1;
        $engagements = $insights->likes + $insights->comments + $insights->shares;

        return ($engagements / $impressions) * 100;
    }
}