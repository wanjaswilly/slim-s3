<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $table = 'productreviews';

    protected $fillable = [
        'product_id',
        'shop_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'response',
        'responded_at',
        'responded_by',
        'is_verified',
        'is_approved',
        'is_featured',
        'helpful_count',
        'not_helpful_count',
        'reported_count',
        'media_urls',
        'pros',
        'cons',
        'usage_duration',
        'verified_purchase',
        'metadata'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'reported_count' => 'integer',
        'responded_at' => 'datetime',
        'media_urls' => 'array',
        'pros' => 'array',
        'cons' => 'array',
        'usage_duration' => 'integer', // in days
        'verified_purchase' => 'boolean',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'rating' => 5,
        'is_verified' => false,
        'is_approved' => true,
        'is_featured' => false,
        'helpful_count' => 0,
        'not_helpful_count' => 0,
        'reported_count' => 0,
        'verified_purchase' => false
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Sale::class, 'order_id');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }


    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeHighRating($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeLowRating($query, $maxRating = 2)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    public function scopeWithComments($query)
    {
        return $query->whereNotNull('comment')->where('comment', '!=', '');
    }

    public function scopeWithMedia($query)
    {
        return $query->whereNotNull('media_urls');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeHelpful($query)
    {
        return $query->where('helpful_count', '>', 0)
                    ->orderByRaw('(helpful_count - not_helpful_count) DESC');
    }

    public function scopeNeedsModeration($query)
    {
        return $query->where('is_approved', false)
                    ->orWhere('reported_count', '>', 0);
    }

    // Methods
    public function getStarRating()
    {
        return str_repeat('⭐', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    public function getHelpfulnessScore()
    {
        $totalVotes = $this->helpful_count + $this->not_helpful_count;
        
        if ($totalVotes === 0) {
            return 0;
        }

        return ($this->helpful_count / $totalVotes) * 100;
    }

    public function markHelpful($customerId = null)
    {
        $this->increment('helpful_count');

        if ($customerId) {
            $this->helpfulVotes()->create([
                'customer_id' => $customerId,
                'type' => 'helpful'
            ]);
        }

        return $this;
    }

    public function markNotHelpful($customerId = null)
    {
        $this->increment('not_helpful_count');

        if ($customerId) {
            $this->notHelpfulVotes()->create([
                'customer_id' => $customerId,
                'type' => 'not_helpful'
            ]);
        }

        return $this;
    }

    public function report($customerId, $reason)
    {
        $this->increment('reported_count');

        $this->reports()->create([
            'customer_id' => $customerId,
            'reason' => $reason
        ]);

        // Auto-moderate if too many reports
        if ($this->reported_count >= 5) {
            $this->update(['is_approved' => false]);
        }

        return $this;
    }

    public function approve()
    {
        $this->update([
            'is_approved' => true,
            'reported_count' => 0 // Reset reports when approved
        ]);

        // Delete all reports when approved
        $this->reports()->delete();

        return $this;
    }

    public function reject($reason = '')
    {
        $this->update([
            'is_approved' => false,
            'metadata' => array_merge($this->metadata ?? [], [
                'rejection_reason' => $reason,
                'rejected_at' => Carbon::now()->toISOString()
            ])
        ]);

        return $this;
    }

    public function feature()
    {
        $this->update(['is_featured' => true]);
        return $this;
    }

    public function unfeature()
    {
        $this->update(['is_featured' => false]);
        return $this;
    }

    public function addResponse($response, $responderId)
    {
        $this->update([
            'response' => $response,
            'responded_by' => $responderId,
            'responded_at' => Carbon::now()
        ]);

        return $this;
    }

    public function removeResponse()
    {
        $this->update([
            'response' => null,
            'responded_by' => null,
            'responded_at' => null
        ]);

        return $this;
    }

    public function hasResponse()
    {
        return !is_null($this->response) && !is_null($this->responded_at);
    }

    public function addMedia($mediaUrl, $type = 'image')
    {
        $mediaUrls = $this->media_urls ?? [];
        
        $mediaUrls[] = [
            'url' => $mediaUrl,
            'type' => $type,
            'added_at' => Carbon::now()->toISOString()
        ];

        $this->update(['media_urls' => $mediaUrls]);
        return $this;
    }

    public function getMediaUrls()
    {
        return $this->media_urls ?? [];
    }

    public function addPro($pro)
    {
        $pros = $this->pros ?? [];
        
        if (!in_array($pro, $pros)) {
            $pros[] = $pro;
            $this->update(['pros' => $pros]);
        }

        return $this;
    }

    public function addCon($con)
    {
        $cons = $this->cons ?? [];
        
        if (!in_array($con, $cons)) {
            $cons[] = $con;
            $this->update(['cons' => $cons]);
        }

        return $this;
    }

    public function getSentiment()
    {
        if ($this->rating >= 4) {
            return 'positive';
        } elseif ($this->rating == 3) {
            return 'neutral';
        } else {
            return 'negative';
        }
    }

    public function isPositive()
    {
        return $this->rating >= 4;
    }

    public function isNegative()
    {
        return $this->rating <= 2;
    }

    public function isNeutral()
    {
        return $this->rating == 3;
    }

    public function getWordCount()
    {
        return $this->comment ? str_word_count($this->comment) : 0;
    }

    public function isDetailed()
    {
        return $this->getWordCount() >= 10;
    }

    public static function createFromOrder($orderId, $productId, $customerId, $rating, $comment = null)
    {
        $order = Sale::find($orderId);
        $product = Product::find($productId);
        $customer = Customer::find($customerId);

        if (!$order || !$product || !$customer) {
            return null;
        }

        // Check if customer already reviewed this product from this order
        $existingReview = static::where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('customer_id', $customerId)
            ->first();

        if ($existingReview) {
            return $existingReview;
        }

        $review = new static([
            'product_id' => $productId,
            'shop_id' => $product->shop_id,
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'rating' => $rating,
            'comment' => $comment,
            'is_verified' => true,
            'verified_purchase' => true
        ]);

        $review->save();
        return $review;
    }

    public function updateProductRating()
    {
        $product = $this->product;
        if (!$product) {
            return;
        }

        $reviews = $product->reviews()->approved()->get();
        
        if ($reviews->isEmpty()) {
            return;
        }

        $averageRating = $reviews->avg('rating');
        $totalReviews = $reviews->count();

        // Update product rating (you might want to store this in products table)
        // For now, we'll just recalculate when needed
        return [
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $totalReviews,
            'rating_breakdown' => $reviews->groupBy('rating')->map->count()
        ];
    }

    public function shouldBeFeatured()
    {
        return $this->isDetailed() && 
               $this->isPositive() && 
               $this->getHelpfulnessScore() >= 80 &&
               $this->helpful_count >= 5;
    }
}