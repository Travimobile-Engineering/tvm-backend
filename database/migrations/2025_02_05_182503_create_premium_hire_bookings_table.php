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
        Schema::create('premium_hire_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('uuid');
            $table->bigInteger('driver_id');
            $table->bigInteger('vehicle_id');
            $table->bigInteger('payment_log_id');
            $table->integer('number_of_vehicles');
            $table->string('ticket_type')->nullable();
            $table->integer('departure_id');
            $table->integer('destination_id');
            $table->longText('bus_stops');
            $table->longText('luggage');
            $table->string('amount');
            $table->string('payment_type');
            $table->string('payment_status');
            $table->string('payment_method');
            $table->string('date')->nullable();
            $table->text('reason')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('premium_hire_bookings');
    }
};
