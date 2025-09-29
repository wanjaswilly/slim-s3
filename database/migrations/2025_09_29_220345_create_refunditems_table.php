<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateRefundItemsTable
{
    public function up()
    {

        Capsule::schema()->create('refunditems', function ($table) {
            $table->id();

            $table->foreignId('refund_id')->constrained('refunds')->onDelete('cascade');
            $table->foreignId('sale_item_id')->constrained('saleitems')->onDelete('cascade');
            $table->integer('quantity_refunded');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('refund_amount', 10, 2);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['refund_id', 'sale_item_id']);
            $table->index('quantity_refunded');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('refunditems');
    }
}
