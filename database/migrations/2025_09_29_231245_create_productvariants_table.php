<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateProductVariantsTable
{
    public function up()
    {

        Capsule::schema()->create('productvariants', function ($table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->string('name')->nullable();
            $table->string('option1')->nullable(); // Size, Color, etc.
            $table->string('option2')->nullable();
            $table->string('option3')->nullable();
            $table->json('option_values')->nullable(); // Structured option data
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('profit_margin', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('weight_unit')->default('kg');
            $table->json('dimensions')->nullable(); // {length, width, height}
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('track_quantity')->default(true);
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_available')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->integer('reorder_point')->default(10);
            $table->integer('reorder_quantity')->default(25);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'low_stock', 'pre_order', 'discontinued'])->default('out_of_stock');
            $table->json('images_order')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'sku']);
            $table->unique(['shop_id', 'barcode']);
            $table->index(['product_id', 'parent_id']);
            $table->index(['shop_id', 'stock_status']);
            $table->index(['quantity_available', 'is_active']);
            $table->index(['option1', 'option2', 'option3']);
            $table->index('is_default');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('productvariants');
    }
}
