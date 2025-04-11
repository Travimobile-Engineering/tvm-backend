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
        Schema::table('trip_booking_passengers', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone_number')->nullable()->change();
            $table->string('gender')->nullable()->change();
        });
    }
};
