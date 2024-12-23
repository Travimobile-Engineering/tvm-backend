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
        Schema::create('transports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('departure');
            $table->string('destination');
            $table->string('departure_date');
            $table->string('departure_time');
            $table->string('repeat_trip')->nullable();
            $table->string('start_date')->nullable();
            $table->json('trip_days')->nullable();
            $table->string('bus_type');
            $table->string('ticket_price');
            $table->json('bus_stops');
            $table->enum('type', ['one-time', 'recurring'])->default('one-time');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transports');
    }
};
