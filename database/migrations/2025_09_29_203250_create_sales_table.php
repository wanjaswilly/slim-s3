<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateSalesTable
{
    public function up()
    {

        Capsule::schema()->create('sales', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('sale_number')->unique();
            $table->enum('status', ['draft', 'pending', 'confirmed', 'completed', 'cancelled', 'refunded'])->default('draft');
            $table->enum('payment_status', ['pending', 'paid', 'partial', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('channel')->default('in_store');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('KES');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('sale_date')->useCurrent();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['seller_id', 'sale_date']);
            $table->index(['customer_id', 'payment_status']);
            $table->index(['sale_date', 'channel']);
            $table->index('payment_status');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('sales');
    }
}
