<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreatePostInsightsTable
{
    public function up()
    {

        Capsule::schema()->create('postinsights', function ($table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('media_id')->nullable()->constrained('postmedias')->onDelete('set null');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained('channels')->onDelete('set null');
            $table->string('platform'); // facebook, instagram, tiktok, etc.
            $table->string('external_post_id')->nullable(); // ID from the platform
            $table->enum('period', ['lifetime', 'daily', 'weekly', 'monthly'])->default('lifetime');
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('engagement')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('saves')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('profile_visits')->default(0);
            $table->integer('follows')->default(0);
            $table->integer('video_views')->default(0);
            $table->decimal('video_completion_rate', 5, 2)->default(0);
            $table->integer('story_views')->default(0);
            $table->integer('story_replies')->default(0);
            $table->integer('link_clicks')->default(0);
            $table->decimal('ctr', 8, 4)->default(0); // Click-through rate
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->decimal('cost_per_click', 8, 4)->default(0);
            $table->decimal('spend', 10, 2)->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_value', 10, 2)->default(0);
            $table->json('audience_demographics')->nullable();
            $table->json('top_locations')->nullable();
            $table->json('peak_engagement_times')->nullable();
            $table->json('hashtag_performance')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['post_id', 'platform', 'period', 'date']);
            $table->index(['shop_id', 'platform', 'date']);
            $table->index(['channel_id', 'period']);
            $table->index(['engagement', 'created_at']);
            $table->index('external_post_id');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('postinsights');
    }
}
