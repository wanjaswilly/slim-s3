<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUserDevicesTable
{
    public function up()
    {

        Capsule::schema()->create('userdevices', function ($table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id');
            $table->enum('device_type', ['mobile', 'tablet', 'desktop']);
            $table->string('push_token')->nullable();
            $table->string('platform'); // ios, android, web
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('userdevices');
    }
}
