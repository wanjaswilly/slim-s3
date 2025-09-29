<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateSocialAccountsTable
{
    public function up()
    {

        Capsule::schema()->create('socialaccounts', function ($table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // facebook, google, twitter, etc.
            $table->string('provider_id');
            $table->text('token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('provider_data')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->index(['user_id', 'provider']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('socialaccounts');
    }
}
