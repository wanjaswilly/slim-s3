<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PostInsight extends Model
{
    protected $table = 'postinsights';

    protected $fillable = [
        'post_id',
        'media_id',
        'shop_id',
        'channel_id',
        'platform',
        'external_post_id',
        'period',
        'date',
        'impressions',
        'reach',
        'engagement',
        'likes',
        'comments',
        'shares',
        'saves',
        'clicks',
        'profile_visits',
        'follows',
        'video_views',
        'video_completion_rate',
        'story_views',
        'story_replies',
        'link_clicks',
        'ctr',
        'engagement_rate',
        'cost_per_click',
        'spend',
        'conversions',
        'conversion_value',
        'audience_demographics',
        'top_locations',
        'peak_engagement_times',
        'hashtag_performance',
        'metadata'
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'reach' => 'integer',
        'engagement' => 'integer',
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'saves' => 'integer',
        'clicks' => 'integer',
        'profile_visits' => 'integer',
        'follows' => 'integer',
        'video_views' => 'integer',
        'video_completion_rate' => 'decimal:2',
        'story_views' => 'integer',
        'story_replies' => 'integer',
        'link_clicks' => 'integer',
        'ctr' => 'decimal:4',
        'engagement_rate' => 'decimal:4',
        'cost_per_click' => 'decimal:4',
        'spend' => 'decimal:2',
        'conversions' => 'integer',
        'conversion_value' => 'decimal:2',
        'audience_demographics' => 'array',
        'top_locations' => 'array',
        'peak_engagement_times' => 'array',
        'hashtag_performance' => 'array',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'period' => 'lifetime'
    ];

    // Relationships
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function media()
    {
        return $this->belongsTo(PostMedia::class, 'media_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    // Scopes
    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('date', '>=', Carbon::now()->subDays($days));
    }

    public function scopeHighEngagement($query, $threshold = 1000)
    {
        return $query->where('engagement', '>=', $threshold);
    }

    public function scopeHighReach($query, $threshold = 5000)
    {
        return $query->where('reach', '>=', $threshold);
    }

    public function scopeByChannel($query, $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeWithVideoMetrics($query)
    {
        return $query->where('video_views', '>', 0);
    }

    // Methods
    public function calculateEngagementRate()
    {
        if ($this->reach > 0) {
            $this->engagement_rate = ($this->engagement / $this->reach) * 100;
        } else {
            $this->engagement_rate = 0;
        }
        
        return $this;
    }

    public function calculateCTR()
    {
        if ($this->impressions > 0) {
            $this->ctr = ($this->clicks / $this->impressions) * 100;
        } else {
            $this->ctr = 0;
        }
        
        return $this;
    }

    public function getTotalEngagement()
    {
        return $this->likes + $this->comments + $this->shares + $this->saves;
    }

    public function updateEngagementMetrics()
    {
        $this->engagement = $this->getTotalEngagement();
        $this->calculateEngagementRate();
        $this->calculateCTR();
        $this->save();
    }

    public function getPerformanceScore()
    {
        $score = 0;
        
        // Engagement rate contribution (max 40 points)
        $engagementRate = $this->engagement_rate ?? 0;
        $score += min($engagementRate * 4, 40);
        
        // CTR contribution (max 30 points)
        $ctr = $this->ctr ?? 0;
        $score += min($ctr * 300, 30); // Assuming 0.1% CTR = 30 points
        
        // Reach contribution (max 20 points)
        $reach = $this->reach ?? 0;
        if ($reach > 10000) $score += 20;
        elseif ($reach > 5000) $score += 15;
        elseif ($reach > 1000) $score += 10;
        elseif ($reach > 500) $score += 5;
        
        // Video completion contribution (max 10 points)
        if ($this->video_completion_rate) {
            $score += min($this->video_completion_rate / 10, 10);
        }
        
        return min($score, 100);
    }

    public function getPerformanceLabel()
    {
        $score = $this->getPerformanceScore();
        
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Average';
        if ($score >= 20) return 'Poor';
        return 'Very Poor';
    }

    public function getCostPerEngagement()
    {
        if ($this->engagement > 0 && $this->spend > 0) {
            return $this->spend / $this->engagement;
        }
        return 0;
    }

    public function getROI()
    {
        if ($this->spend > 0) {
            return ($this->conversion_value - $this->spend) / $this->spend * 100;
        }
        return 0;
    }

    public function getTopCountries($limit = 5)
    {
        $locations = $this->top_locations ?? [];
        
        return collect($locations)
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    public function getAgeDemographics()
    {
        $demographics = $this->audience_demographics ?? [];
        return $demographics['age'] ?? [];
    }

    public function getGenderDemographics()
    {
        $demographics = $this->audience_demographics ?? [];
        return $demographics['gender'] ?? [];
    }

    public function getBestPerformingHashtags($limit = 5)
    {
        $hashtags = $this->hashtag_performance ?? [];
        
        return collect($hashtags)
            ->sortByDesc('engagement')
            ->take($limit)
            ->values()
            ->all();
    }

    public static function recordDailyInsight($postId, $platform, $data)
    {
        $post = Post::find($postId);
        if (!$post) {
            return null;
        }

        $insight = static::updateOrCreate(
            [
                'post_id' => $postId,
                'platform' => $platform,
                'date' => Carbon::now()->toDateString(),
                'period' => 'daily'
            ],
            array_merge($data, [
                'shop_id' => $post->shop_id,
                'channel_id' => $post->channels()->where('type', $platform)->first()?->id
            ])
        );

        $insight->updateEngagementMetrics();
        return $insight;
    }

    public static function aggregateToLifetime($postId, $platform)
    {
        $dailyInsights = static::where('post_id', $postId)
            ->where('platform', $platform)
            ->where('period', 'daily')
            ->get();

        if ($dailyInsights->isEmpty()) {
            return null;
        }

        $lifetimeData = [
            'impressions' => $dailyInsights->sum('impressions'),
            'reach' => $dailyInsights->sum('reach'),
            'likes' => $dailyInsights->sum('likes'),
            'comments' => $dailyInsights->sum('comments'),
            'shares' => $dailyInsights->sum('shares'),
            'saves' => $dailyInsights->sum('saves'),
            'clicks' => $dailyInsights->sum('clicks'),
            'profile_visits' => $dailyInsights->sum('profile_visits'),
            'follows' => $dailyInsights->sum('follows'),
            'video_views' => $dailyInsights->sum('video_views'),
            'spend' => $dailyInsights->sum('spend'),
            'conversions' => $dailyInsights->sum('conversions'),
            'conversion_value' => $dailyInsights->sum('conversion_value'),
        ];

        // Calculate averages
        $lifetimeData['engagement_rate'] = $lifetimeData['reach'] > 0 ? 
            ($dailyInsights->sum('engagement') / $lifetimeData['reach']) * 100 : 0;
        
        $lifetimeData['ctr'] = $lifetimeData['impressions'] > 0 ? 
            ($lifetimeData['clicks'] / $lifetimeData['impressions']) * 100 : 0;

        $lifetimeData['engagement'] = $dailyInsights->sum('engagement');

        $lifetimeInsight = static::updateOrCreate(
            [
                'post_id' => $postId,
                'platform' => $platform,
                'period' => 'lifetime'
            ],
            array_merge($lifetimeData, [
                'shop_id' => $dailyInsights->first()->shop_id,
                'channel_id' => $dailyInsights->first()->channel_id,
                'date' => Carbon::now()->toDateString()
            ])
        );

        return $lifetimeInsight;
    }

    public function getGrowthFromPrevious($previousInsight)
    {
        if (!$previousInsight) {
            return 0;
        }

        $currentValue = $this->engagement;
        $previousValue = $previousInsight->engagement;

        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }

        return (($currentValue - $previousValue) / $previousValue) * 100;
    }
}