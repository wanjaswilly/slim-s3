<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateInvoicesTable
{
    public function up()
    {

        Capsule::schema()->create('invoices', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->onDelete('set null');
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('number')->unique();
            $table->enum('status', ['draft', 'open', 'paid', 'void', 'uncollectible'])->default('draft');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_date')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('amount_due', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('amount_remaining', 10, 2)->default(0);
            $table->string('currency', 3)->default('KES');
            $table->string('invoice_pdf_url')->nullable();
            $table->string('hosted_invoice_url')->nullable();
            $table->string('billing_reason')->nullable();
            $table->integer('attempt_count')->default(0);
            $table->timestamp('next_payment_attempt')->nullable();
            $table->json('metadata')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['subscription_id', 'created_at']);
            $table->index(['due_date', 'status']);
            $table->index('stripe_invoice_id');
            $table->index('number');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('invoices');
    }
}
