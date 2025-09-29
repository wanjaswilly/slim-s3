<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateSubscriptionsTable
{
    public function up()
    {

        Capsule::schema()->create('subscriptions', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('name');
            $table->string('stripe_id')->nullable();
            $table->string('stripe_status')->nullable();
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shop_id']); // One subscription per shop
            $table->index(['stripe_id', 'stripe_status']);
            $table->index(['trial_ends_at', 'ends_at']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('subscriptions');
    }
}
