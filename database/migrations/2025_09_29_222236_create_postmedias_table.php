<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreatePostMediasTable
{
    public function up()
    {

        Capsule::schema()->create('postmedias', function ($table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->enum('media_type', ['image', 'video', 'audio', 'document', 'gif', 'carousel'])->default('image');
            $table->string('file_url');
            $table->string('thumbnail_url')->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->integer('duration')->nullable(); // in seconds, for video/audio
            $table->json('dimensions')->nullable(); // {width, height} for images/videos
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->json('platform_data')->nullable(); // Platform-specific data
            $table->softDeletes();
            $table->timestamps();

            $table->index(['post_id', 'media_type']);
            $table->index(['shop_id', 'created_at']);
            $table->index(['is_primary', 'sort_order']);
            $table->index('file_size');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('postmedias');
    }
}
