<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateMessageAttachmentsTable
{
    public function up()
    {

        Capsule::schema()->create('messageattachments', function ($table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->string('file_url');
            $table->string('thumbnail_url')->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->string('file_type')->nullable(); // image, video, audio, document, etc.
            $table->json('dimensions')->nullable(); // {width, height} for images/videos
            $table->integer('duration')->nullable(); // for video/audio in seconds
            $table->text('caption')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['message_id', 'file_type']);
            $table->index('file_size');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('messageattachments');
    }
}
