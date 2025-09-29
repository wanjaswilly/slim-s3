<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateCategorysTable
{
    public function up()
    {

        Capsule::schema()->create('categorys', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('seo_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'slug']);
            $table->index(['shop_id', 'parent_id']);
            $table->index(['is_active', 'is_featured']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('categorys');
    }
}
