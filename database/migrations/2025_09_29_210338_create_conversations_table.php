<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateConversationsTable
{
    public function up()
    {

        Capsule::schema()->create('conversations', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('channel_id')->constrained('channels')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('external_id')->nullable(); // ID from external platform
            $table->string('subject')->nullable();
            $table->enum('status', ['open', 'closed', 'archived'])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview')->nullable();
            $table->integer('unread_count')->default(0);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'external_id']);
            $table->index(['shop_id', 'status']);
            $table->index(['assigned_to', 'priority']);
            $table->index(['last_message_at', 'unread_count']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('conversations');
    }
}
