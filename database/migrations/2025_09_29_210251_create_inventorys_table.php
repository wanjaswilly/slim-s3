<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateInventorysTable
{
    public function up()
    {

        Capsule::schema()->create('inventorys', function ($table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_ordered')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->integer('reorder_point')->default(10);
            $table->integer('reorder_quantity')->default(25);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamp('last_sold_at')->nullable();
            $table->decimal('stock_value', 10, 2)->default(0);
            $table->decimal('average_cost', 10, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['product_id']);
            $table->index(['shop_id', 'quantity_available']);
            $table->index(['last_restocked_at', 'reorder_point']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('inventorys');
    }
}
