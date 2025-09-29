<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateRefundsTable
{
    public function up()
    {

        Capsule::schema()->create('refunds', function ($table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('refund_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->text('reason');
            $table->enum('status', ['pending', 'processed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('processor_reference')->nullable();
            $table->json('processor_response')->nullable();
            $table->enum('refund_method', ['original', 'credit', 'store_credit', 'bank_transfer'])->default('original');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status']);
            $table->index(['shop_id', 'created_at']);
            $table->index(['customer_id', 'status']);
            $table->index(['refund_number', 'refunded_at']);
            $table->index('processor_reference');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('refunds');
    }
}
