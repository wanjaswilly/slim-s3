<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateReceiptsTable
{
    public function up()
    {

        Capsule::schema()->create('receipts', function ($table) {
            $table->id();

            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->string('receipt_number')->unique();
            $table->enum('type', ['sale', 'refund', 'void'])->default('sale');
            $table->enum('status', ['draft', 'issued', 'voided'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->string('pdf_url')->nullable();
            $table->text('html_content')->nullable();
            $table->json('data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'type']);
            $table->index(['receipt_number', 'status']);
            $table->index('issued_at');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('receipts');
    }
}
