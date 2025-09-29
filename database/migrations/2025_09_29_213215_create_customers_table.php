<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateCustomersTable
{
    public function up()
    {

        Capsule::schema()->create('customers', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('customer_type', ['individual', 'business'])->default('individual');
            $table->string('company_name')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('currency', 3)->default('KES');
            $table->string('language', 10)->default('en');
            $table->string('timezone')->default('Africa/Nairobi');
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->timestamp('last_purchase_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'email']);
            $table->unique(['shop_id', 'phone']);
            $table->index(['shop_id', 'customer_type']);
            $table->index(['shop_id', 'total_spent']);
            $table->index('last_purchase_at');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('customers');
    }
}
