<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    protected $table = 'conversations';

    protected $fillable = [
        'shop_id',
        'channel_id',
        'customer_id',
        'external_id',
        'subject',
        'status',
        'priority',
        'assigned_to',
        'last_message_at',
        'last_message_preview',
        'unread_count',
        'tags',
        'metadata'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread_count' => 'integer',
        'tags' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('unread_count', '>', 0);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    // Methods
    public function markAsRead()
    {
        $this->update(['unread_count' => 0]);
    }

    public function incrementUnread()
    {
        $this->increment('unread_count');
    }

    public function assignTo($userId)
    {
        $this->update(['assigned_to' => $userId]);
    }

    public function close()
    {
        $this->update(['status' => 'closed']);
    }

    public function reopen()
    {
        $this->update(['status' => 'open']);
    }

    public function addParticipant($userId)
    {
        return $this->participants()->attach($userId);
    }

    public function removeParticipant($userId)
    {
        return $this->participants()->detach($userId);
    }

    public function addMessage($content, $senderId, $type = 'text', $metadata = [])
    {
        $message = $this->messages()->create([
            'sender_id' => $senderId,
            'content' => $content,
            'type' => $type,
            'metadata' => $metadata
        ]);

        $this->update([
            'last_message_at' => Carbon::now(),
            'last_message_preview' => substr($content, 0, 100),
            'unread_count' => $this->unread_count + 1
        ]);

        return $message;
    }

    public function getLastMessage()
    {
        return $this->messages()->latest()->first();
    }
}