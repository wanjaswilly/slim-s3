<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateCarrierAccountsTable
{
    public function up()
    {

        Capsule::schema()->create('carrieraccounts', function ($table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->string('carrier_name'); // FedEx, UPS, DHL, etc.
            $table->string('code')->unique(); // fedex, ups, dhl
            $table->string('account_number')->nullable();
            $table->string('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->boolean('test_mode')->default(true);
            $table->boolean('is_active')->default(false);
            $table->json('credentials')->nullable(); // Encrypted additional credentials
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
            $table->index(['carrier_name', 'is_active']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('carrieraccounts');
    }
}
