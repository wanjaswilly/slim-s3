<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageAttachment extends Model
{
    protected $table = 'messageattachments';

    protected $fillable = [
        'message_id',
        'file_url',
        'thumbnail_url',
        'file_name',
        'file_size',
        'mime_type',
        'file_type',
        'dimensions',
        'duration',
        'caption',
        'metadata'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'duration' => 'integer',
        'dimensions' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function message()
    {
        return $this->belongsTo(Message::class);
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

    public function isImage()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo()
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isAudio()
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    public function isDocument()
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}