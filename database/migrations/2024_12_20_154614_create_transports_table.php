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
            $table->string('departure_date')->nullable();
            $table->string('departure_time')->nullable();
            $table->string('repeat_trip')->nullable();
            $table->string('reoccur_duration')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->json('trip_days')->nullable();
            $table->string('bus_type');
            $table->string('ticket_price');
            $table->json('bus_stops');
            $table->enum('type', ['one-time', 'recurring'])->default('one-time');
            $table->longText('reason')->nullable();
            $table->dateTime('date_cancelled')->nullable();
            $table->enum('status', ['active', 'completed', 'in-progress', 'cancelled'])
                ->default('active');
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
