<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateShippingsTable
{
    public function up()
    {

        Capsule::schema()->create('shippings', function ($table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('shipping_method'); // standard, express, overnight, pickup
            $table->string('carrier'); // fedex, ups, dhl, usps, local, custom
            $table->string('tracking_number')->nullable();
            $table->enum('status', [
                'pending',
                'confirmed',
                'shipped',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'failed',
                'returned',
                'cancelled'
            ])->default('pending');
            $table->enum('package_type', ['package', 'envelope', 'tube', 'pallet'])->default('package');
            $table->decimal('package_weight', 8, 3)->nullable(); // in kg
            $table->json('package_dimensions')->nullable(); // {length, width, height} in cm
            $table->decimal('insurance_amount', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('handling_fee', 10, 2)->default(0);
            $table->timestamp('estimated_delivery_date')->nullable();
            $table->timestamp('actual_delivery_date')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->json('from_address')->nullable();
            $table->json('to_address')->nullable();
            $table->string('label_url')->nullable();
            $table->json('tracking_events')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['sale_id']); // One shipping per sale
            $table->index(['shop_id', 'status']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['carrier', 'tracking_number']);
            $table->index(['estimated_delivery_date', 'status']);
            $table->index('shipped_at');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('shippings');
    }
}
