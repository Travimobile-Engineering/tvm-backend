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
        Schema::create('driver_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('vehicle_year');
            $table->string('vehicle_model');
            $table->string('vehicle_color');
            $table->string('plate_number');
            $table->string('vehicle_type');
            $table->string('vehicle_capacity');
            $table->json('seats');
            $table->integer('seat_row');
            $table->integer('seat_column');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('image_url')->nullable();
            $table->string('public_id')->nullable();
            $table->string('number')->nullable();
            $table->string('expiration_date')->nullable();
            $table->string('status');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('driver_documents');
    }
};
