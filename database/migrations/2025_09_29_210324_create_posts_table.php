<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreatePostsTable
{
    public function up()
    {

        Capsule::schema()->create('posts', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['product', 'promotional', 'informational', 'announcement']);
            $table->string('title');
            $table->text('content');
            $table->text('excerpt')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['user_id', 'type']);
            $table->index(['scheduled_for', 'published_at']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('posts');
    }
}
