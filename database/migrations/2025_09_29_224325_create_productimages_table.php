<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateProductImagesTable
{
    public function up()
    {

        Capsule::schema()->create('productimages', function ($table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->string('image_url');
            $table->string('thumbnail_url')->nullable();
            $table->string('large_url')->nullable();
            $table->string('original_url')->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->json('dimensions')->nullable(); // {width, height}
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->string('color_variant')->nullable();
            $table->json('platform_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'is_primary']);
            $table->index(['shop_id', 'created_at']);
            $table->index(['sort_order', 'is_visible']);
            $table->index('color_variant');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('productimages');
    }
}
