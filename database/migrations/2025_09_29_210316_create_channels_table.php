<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateChannelsTable
{
    public function up()
    {

        Capsule::schema()->create('channels', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['facebook', 'instagram', 'whatsapp', 'tiktok', 'twitter', 'website', 'marketplace']);
            $table->string('name');
            $table->string('identifier'); // Page ID, username, etc.
            $table->json('credentials')->nullable(); // Encrypted
            $table->json('settings')->nullable();
            $table->enum('status', ['connected', 'disconnected', 'pending', 'error'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'type', 'identifier']);
            $table->index(['shop_id', 'is_active']);
            $table->index(['type', 'status']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('channels');
    }
}
