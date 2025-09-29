<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUsersTable
{
    public function up()
    {

        Capsule::schema()->create('users', function ($table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin', 'seller', 'staff'])->default('seller');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('timezone')->default('Africa/Nairobi');
            $table->string('language', 10)->default('en');
            $table->string('currency', 3)->default('KES');
            $table->string('avatar_url')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->rememberToken();
            $table->string('verification_token')->nullable();
            $table->json('meta')->nullable();
            $table->json('preferences')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('users');
    }
}
