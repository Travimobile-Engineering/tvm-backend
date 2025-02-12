<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('premium_hire_bookings', function (Blueprint $table) {
            $table->string('pickup_location')->nullable()->after('lng');
            $table->string('dropoff_location')->nullable()->after('pickup_location');
            $table->string('time')->nullable()->after('payment_method');
        });
    }

};
