<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateMessagesTable
{
    public function up()
    {

        Capsule::schema()->create('messages', function ($table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_type')->default('user'); // user, customer, system
            $table->string('external_message_id')->nullable(); // ID from external platform
            $table->unsignedBigInteger('parent_message_id')->nullable(); // For replies
            $table->text('content')->nullable();
            $table->enum('message_type', [
                'text',
                'image',
                'video',
                'audio',
                'document',
                'location',
                'sticker',
                'contact',
                'template',
                'quick_reply',
                'system'
            ])->default('text');
            $table->string('media_url')->nullable();
            $table->string('media_type')->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('reactions')->nullable(); // Store reactions from users
            $table->json('quick_replies')->nullable(); // For quick reply buttons
            $table->json('buttons')->nullable(); // For message buttons
            $table->string('template_id')->nullable(); // For template messages
            $table->json('metadata')->nullable();
            $table->json('platform_data')->nullable(); // Platform-specific data
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'sender_type']);
            $table->index(['external_message_id', 'message_type']);
            $table->index(['parent_message_id', 'created_at']);
            $table->index(['is_read', 'created_at']);
            $table->index(['message_type', 'created_at']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('messages');
    }
}
