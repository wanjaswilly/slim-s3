<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'shop_id',
        'type',
        'title',
        'message',
        'data',
        'action_url',
        'action_label',
        'priority',
        'is_read',
        'scheduled_at',
        'sent_at',
        'read_at',
        'channels'
    ];

    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'is_read' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'read_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Methods
    public function markAsRead()
    {
        return $this->update([
            'is_read' => true,
            'read_at' => Carbon::now()
        ]);
    }

    public function markAsUnread()
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null
        ]);
    }

    public function markAsSent()
    {
        return $this->update(['sent_at' => Carbon::now()]);
    }

    public function shouldSend()
    {
        return !$this->sent_at && (!$this->scheduled_at || $this->scheduled_at <= Carbon::now());
    }

    public function getChannelsAttribute($value)
    {
        $defaultChannels = ['database'];
        
        $channels = $value ? json_decode($value, true) : [];
        return array_merge($defaultChannels, $channels);
    }

    public function addChannel($channel)
    {
        $channels = $this->channels;
        if (!in_array($channel, $channels)) {
            $channels[] = $channel;
            $this->channels = $channels;
            $this->save();
        }
        return $this;
    }

    public function removeChannel($channel)
    {
        $channels = array_diff($this->channels, [$channel]);
        $this->channels = array_values($channels);
        return $this->save();
    }

    public static function createForShop($shopId, $type, $title, $message, $data = [], $priority = 'normal')
    {
        $shop = Shop::find($shopId);
        if (!$shop) return null;

        $notifications = [];
        foreach ($shop->members as $member) {
            $notifications[] = static::create([
                'user_id' => $member->id,
                'shop_id' => $shopId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'priority' => $priority,
                'is_read' => false,
                'channels' => ['database', 'email']
            ]);
        }

        return $notifications;
    }
}