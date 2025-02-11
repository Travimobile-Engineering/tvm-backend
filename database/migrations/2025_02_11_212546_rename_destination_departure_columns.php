<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('premium_hire_bookings', function (Blueprint $table) {
            $table->renameColumn('destination_id', 'lng');
            $table->renameColumn('departure_id', 'lat');

            $table->dropColumn('number_of_vehicles');
        });

        Schema::table('premium_hire_bookings', function (Blueprint $table) {
            $table->string('lng')->change();
            $table->string('lat')->change();
        });
    }
};
