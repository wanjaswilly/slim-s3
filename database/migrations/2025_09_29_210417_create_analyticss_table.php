<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateAnalyticssTable
{
    public function up()
    {

        Capsule::schema()->create('analyticss', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->string('type'); // daily_summary, sales_trend, customer_behavior, etc.
            $table->enum('period', ['hour', 'day', 'week', 'month', 'year'])->default('day');
            $table->date('date');
            $table->json('metrics');
            $table->json('breakdown')->nullable();
            $table->json('comparison')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'type', 'period', 'date']);
            $table->index(['shop_id', 'date']);
            $table->index(['type', 'period']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('analyticss');
    }
}
