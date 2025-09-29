<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateNotificationsTable
{
    public function up()
    {

        Capsule::schema()->create('notifications', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type'); // sale_created, low_stock, payment_received, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->boolean('is_read')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('channels')->nullable(); // ['database', 'email', 'sms', 'push']
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['shop_id', 'type']);
            $table->index(['scheduled_at', 'sent_at']);
            $table->index('priority');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('notifications');
    }
}
