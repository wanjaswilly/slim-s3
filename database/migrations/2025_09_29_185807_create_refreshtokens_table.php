<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateRefreshTokensTable
{
    public function up()
    {

        Capsule::schema()->create('refreshtokens', function ($table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('token');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at', 'revoked_at']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('refreshtokens');
    }
}
