<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateSiteStatsTable
{
    public function up()
    {

        Capsule::schema()->create('sitestats', function ($table) {
            $table->id();


            $table->string('url');
            $table->string('method');
            $table->string('ip')->nullable();
            $table->string('device')->nullable();
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('country')->nullable();
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('sitestats');
    }
}
