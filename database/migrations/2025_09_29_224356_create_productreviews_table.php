<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateProductReviewsTable
{
    public function up()
    {

        Capsule::schema()->create('productreviews', function ($table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('sales')->onDelete('set null');
            $table->integer('rating'); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->integer('reported_count')->default(0);
            $table->json('media_urls')->nullable();
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->integer('usage_duration')->nullable(); // in days
            $table->boolean('verified_purchase')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'rating']);
            $table->index(['shop_id', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['is_approved', 'is_featured']);
            $table->index(['helpful_count', 'created_at']);
            $table->index('verified_purchase');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('productreviews');
    }
}
