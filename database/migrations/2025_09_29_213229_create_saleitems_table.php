<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateSaleItemsTable
{
    public function up()
    {

        Capsule::schema()->create('saleitems', function ($table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sale')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2);
            $table->decimal('line_tax', 10, 2);
            $table->decimal('line_total_with_tax', 10, 2);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'product_id']);
            $table->index(['product_id', 'created_at']);
            $table->index('line_total');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('saleitems');
    }
}
