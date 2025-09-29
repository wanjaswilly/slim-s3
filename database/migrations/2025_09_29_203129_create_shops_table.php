<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateShopsTable
{
    public function up()
    {

        Capsule::schema()->create('shops', function ($table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('business_type')->nullable();
            $table->string('category')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Kenya');
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('timezone')->default('Africa/Nairobi');
            $table->string('currency', 3)->default('KES');
            $table->string('language', 10)->default('en');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('tax_number')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('settings')->nullable();
            $table->json('social_links')->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'is_active']);
            $table->index(['country', 'city']);
            $table->index(['latitude', 'longitude']);
            $table->index(['is_verified', 'is_featured']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('shops');
    }
}
