<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'external_message_id',
        'parent_message_id',
        'content',
        'message_type',
        'media_url',
        'media_type',
        'file_name',
        'file_size',
        'mime_type',
        'thumbnail_url',
        'is_read',
        'read_at',
        'delivered_at',
        'failed_at',
        'failure_reason',
        'reactions',
        'quick_replies',
        'buttons',
        'template_id',
        'metadata',
        'platform_data'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'reactions' => 'array',
        'quick_replies' => 'array',
        'buttons' => 'array',
        'metadata' => 'array',
        'platform_data' => 'array'
    ];

    protected $attributes = [
        'message_type' => 'text',
        'sender_type' => 'user',
        'is_read' => false
    ];

    // Relationships
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->morphTo();
    }

    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_message_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_message_id');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
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

    public function scopeBySender($query, $senderId, $senderType = 'user')
    {
        return $query->where('sender_id', $senderId)
            ->where('sender_type', $senderType);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeWithMedia($query)
    {
        return $query->whereNotNull('media_url');
    }

    public function scopeFailed($query)
    {
        return $query->whereNotNull('failed_at');
    }

    public function scopeDelivered($query)
    {
        return $query->whereNotNull('delivered_at');
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeThisWeek($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->startOfWeek());
    }

    // Methods
    public function markAsRead($readAt = null)
    {
        $this->update([
            'is_read' => true,
            'read_at' => $readAt ?? Carbon::now()
        ]);

        // Update conversation unread count
        if ($this->conversation) {
            $this->conversation->decrement('unread_count');
        }
    }

    public function markAsDelivered($deliveredAt = null)
    {
        $this->update([
            'delivered_at' => $deliveredAt ?? Carbon::now()
        ]);
    }

    public function markAsFailed($reason = '')
    {
        $this->update([
            'failed_at' => Carbon::now(),
            'failure_reason' => $reason
        ]);
    }

    public function isFromCustomer()
    {
        return $this->sender_type === 'customer';
    }

    public function isFromUser()
    {
        return $this->sender_type === 'user';
    }

    public function isFromSystem()
    {
        return $this->sender_type === 'system';
    }

    public function hasMedia()
    {
        return !is_null($this->media_url);
    }

    public function isText()
    {
        return $this->message_type === 'text';
    }

    public function isImage()
    {
        return $this->message_type === 'image';
    }

    public function isVideo()
    {
        return $this->message_type === 'video';
    }

    public function isAudio()
    {
        return $this->message_type === 'audio';
    }

    public function isDocument()
    {
        return $this->message_type === 'document';
    }

    public function isLocation()
    {
        return $this->message_type === 'location';
    }

    public function isTemplate()
    {
        return $this->message_type === 'template';
    }

    public function isQuickReply()
    {
        return $this->message_type === 'quick_reply';
    }

    public function isReply()
    {
        return !is_null($this->parent_message_id);
    }

    public function getPreview($length = 100)
    {
        if ($this->isText()) {
            return strlen($this->content) > $length
                ? substr($this->content, 0, $length) . '...'
                : $this->content;
        }

        $previews = [
            'image' => '🖼️ Image',
            'video' => '🎥 Video',
            'audio' => '🎵 Audio',
            'document' => '📄 Document',
            'location' => '📍 Location',
            'template' => '📋 Template',
            'quick_reply' => '💬 Quick Reply',
            'sticker' => '😀 Sticker',
            'contact' => '👤 Contact'
        ];

        return $previews[$this->message_type] ?? '📎 Attachment';
    }

    public function addReaction($reaction, $userId)
    {
        $reactions = $this->reactions ?? [];

        // Remove existing reaction from this user
        $reactions = array_filter($reactions, function ($r) use ($userId) {
            return $r['user_id'] != $userId;
        });

        // Add new reaction
        $reactions[] = [
            'reaction' => $reaction,
            'user_id' => $userId,
            'reacted_at' => Carbon::now()->toISOString()
        ];

        $this->reactions = $reactions;
        $this->save();

        return $this;
    }

    public function removeReaction($userId)
    {
        $reactions = $this->reactions ?? [];
        $reactions = array_filter($reactions, function ($r) use ($userId) {
            return $r['user_id'] != $userId;
        });

        $this->reactions = array_values($reactions);
        $this->save();

        return $this;
    }

    public function getReactionsSummary()
    {
        $reactions = $this->reactions ?? [];

        $summary = [];
        foreach ($reactions as $reaction) {
            $emoji = $reaction['reaction'];
            $summary[$emoji] = ($summary[$emoji] ?? 0) + 1;
        }

        return $summary;
    }

    public function hasReactionFromUser($userId)
    {
        $reactions = $this->reactions ?? [];

        foreach ($reactions as $reaction) {
            if ($reaction['user_id'] == $userId) {
                return $reaction['reaction'];
            }
        }

        return null;
    }

    public function addQuickReply($text, $payload = null)
    {
        $quickReplies = $this->quick_replies ?? [];

        $quickReplies[] = [
            'text' => $text,
            'payload' => $payload ?? $text,
            'added_at' => Carbon::now()->toISOString()
        ];

        $this->quick_replies = $quickReplies;
        $this->save();

        return $this;
    }

    public function addButton($type, $title, $payload = null, $url = null)
    {
        $buttons = $this->buttons ?? [];

        $button = [
            'type' => $type, // web_url, postback, phone_number, etc.
            'title' => $title,
            'payload' => $payload,
            'added_at' => Carbon::now()->toISOString()
        ];

        if ($url) {
            $button['url'] = $url;
        }

        $buttons[] = $button;
        $this->buttons = $buttons;
        $this->save();

        return $this;
    }

    public function reply($content, $senderId, $senderType = 'user', $messageType = 'text')
    {
        return $this->conversation->addMessage(
            $content,
            $senderId,
            $senderType,
            $messageType,
            $this->id // parent message ID
        );
    }

    public function forwardToConversation($targetConversationId, $senderId)
    {
        $targetConversation = Conversation::find($targetConversationId);

        if (!$targetConversation) {
            return null;
        }

        $forwardedMessage = new static([
            'conversation_id' => $targetConversationId,
            'sender_id' => $senderId,
            'sender_type' => 'user',
            'content' => $this->content,
            'message_type' => $this->message_type,
            'media_url' => $this->media_url,
            'media_type' => $this->media_type,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'metadata' => array_merge($this->metadata ?? [], [
                'forwarded_from' => $this->conversation_id,
                'original_message_id' => $this->id,
                'forwarded_at' => Carbon::now()->toISOString()
            ])
        ]);

        $forwardedMessage->save();
        return $forwardedMessage;
    }

    public function getDeliveryStatus()
    {
        if ($this->failed_at) {
            return 'failed';
        } elseif ($this->delivered_at) {
            return 'delivered';
        } elseif ($this->read_at) {
            return 'read';
        } else {
            return 'sent';
        }
    }

    public function getDeliveryStatusIcon()
    {
        $icons = [
            'sent' => '✓',
            'delivered' => '✓✓',
            'read' => '👁️',
            'failed' => '❌'
        ];

        return $icons[$this->getDeliveryStatus()] ?? '⏳';
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

    public static function createFromWebhook($conversationId, $webhookData)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return null;
        }

        $message = new static([
            'conversation_id' => $conversationId,
            'sender_id' => $webhookData['sender_id'] ?? null,
            'sender_type' => $webhookData['sender_type'] ?? 'customer',
            'external_message_id' => $webhookData['external_message_id'] ?? null,
            'content' => $webhookData['content'] ?? '',
            'message_type' => $webhookData['message_type'] ?? 'text',
            'media_url' => $webhookData['media_url'] ?? null,
            'media_type' => $webhookData['media_type'] ?? null,
            'platform_data' => $webhookData['platform_data'] ?? null,
            'metadata' => $webhookData['metadata'] ?? null
        ]);

        $message->save();

        // Update conversation last message
        $conversation->update([
            'last_message_at' => Carbon::now(),
            'last_message_preview' => $message->getPreview(),
            'unread_count' => $conversation->unread_count + 1
        ]);

        return $message;
    }

    public function shouldTriggerAutoReply()
    {
        // Check if this is a customer message that might need auto-reply
        return $this->isFromCustomer() &&
            $this->isText() &&
            !$this->isReply() &&
            $this->conversation->unread_count <= 1; // First message in conversation
    }

    private function strContainsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, strtolower($needle))) {
                return true;
            }
        }
        return false;
    }


    public function getSuggestedReplies()
    {
        if (!$this->isFromCustomer()) {
            return [];
        }

        $content = strtolower($this->content);
        $suggestedReplies = [];

        // Price inquiry
        if ($this->strContainsAny($content, ['how much', 'price', 'cost', 'how much does it cost'])) {
            $suggestedReplies[] = "The price is KSh X. Would you like more details?";
            $suggestedReplies[] = "Our prices start from KSh X. Which specific product are you interested in?";
        }

        // Location inquiry
        if ($this->strContainsAny($content, ['where', 'location', 'address', 'pick up'])) {
            $suggestedReplies[] = "We're located at [Address]. We're open from 9 AM to 6 PM.";
            $suggestedReplies[] = "You can find us at [Address]. We also offer delivery!";
        }

        // Availability inquiry
        if ($this->strContainsAny($content, ['available', 'in stock', 'do you have'])) {
            $suggestedReplies[] = "Yes, this item is currently in stock!";
            $suggestedReplies[] = "Let me check the availability for you. Which size/color are you interested in?";
        }

        // Delivery inquiry
        if ($this->strContainsAny($content, ['delivery', 'ship', 'deliver'])) {
            $suggestedReplies[] = "We offer delivery within Nairobi for KSh 200.";
            $suggestedReplies[] = "Delivery takes 1-2 business days. We ship nationwide!";
        }

        return array_slice($suggestedReplies, 0, 3); // Return max 3 suggestions
    }
}
