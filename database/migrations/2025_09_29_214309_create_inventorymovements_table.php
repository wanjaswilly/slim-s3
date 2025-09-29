<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateInventoryMovementsTable
{
    public function up()
    {

        Capsule::schema()->create('inventorymovements', function ($table) {
            $table->id();

            $table->foreignId('inventory_id')->constrained('inventorys')->onDelete('cascade');
            $table->foreignId('sale_item_id')->nullable()->constrained('saleitems')->onDelete('set null');
            $table->integer('quantity');
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['inventory_id', 'type']);
            $table->index(['sale_item_id', 'created_at']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('inventorymovements');
    }
}
