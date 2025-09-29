<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreatePlansTable
{
    public function up()
    {

        Capsule::schema()->create('plans', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('stripe_plan_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->enum('type', ['subscription', 'one_time', 'recurring'])->default('subscription');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('KES');
            $table->enum('billing_interval', ['day', 'week', 'month', 'year'])->default('month');
            $table->integer('billing_interval_count')->default(1);
            $table->integer('trial_days')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_visible']);
            $table->index(['type', 'billing_interval']);
            $table->index(['price', 'sort_order']);
            $table->index('stripe_plan_id');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('plans');
    }
}
