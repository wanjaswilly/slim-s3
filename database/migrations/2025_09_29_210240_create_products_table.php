<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateProductsTable
{
    public function up()
    {

        Capsule::schema()->create('products', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->json('specifications')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('unit')->default('pcs');
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('profit_margin', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('weight', 8, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_digital')->default(false);
            $table->boolean('is_virtual')->default(false);
            $table->boolean('track_quantity')->default(true);
            $table->boolean('allow_backorders')->default(false);
            $table->integer('low_stock_threshold')->default(5);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable();
            $table->json('variants')->nullable();
            $table->json('seo_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'slug']);
            $table->unique(['shop_id', 'sku']);
            $table->index(['shop_id', 'is_active']);
            $table->index(['category_id', 'is_featured']);
            $table->fulltext(['name', 'description']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('products');
    }
}
