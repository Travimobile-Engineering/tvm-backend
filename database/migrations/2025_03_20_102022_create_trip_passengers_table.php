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
        Schema::create('trip_booking_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_booking_id')->constrained('trip_bookings')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone_number');
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_phone_number')->nullable();
            $table->string('gender');
            $table->string('selected_seat');
            $table->boolean('on_seat')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_passengers');
    }
};
