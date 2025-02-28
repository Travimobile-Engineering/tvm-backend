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
        Schema::create('premium_hire_booking_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('premium_hire_booking_id')->constrained('premium_hire_bookings')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone_number');
            $table->string('gender');
            $table->string('next_of_kin');
            $table->string('next_of_kin_phone_number');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('premium_hire_booking_passengers');
    }
};
