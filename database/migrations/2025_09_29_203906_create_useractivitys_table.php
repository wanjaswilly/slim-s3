<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUserActivitysTable
{
    public function up()
    {

        Capsule::schema()->create('useractivitys', function ($table) {
            $table->id();
                        
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('useractivitys');
    }
}