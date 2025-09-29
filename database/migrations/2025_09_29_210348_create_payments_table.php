<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreatePaymentsTable
{
    public function up()
    {

        Capsule::schema()->create('payments', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('payment_method');
            $table->string('processor')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('payment_date')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('processor_response')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'transaction_id']);
            $table->index(['sale_id', 'status']);
            $table->index(['customer_id', 'payment_date']);
            $table->index(['payment_method', 'processor']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('payments');
    }
}
