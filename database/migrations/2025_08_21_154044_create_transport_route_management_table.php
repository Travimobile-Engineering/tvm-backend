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
        Schema::create('transport_route_management', function (Blueprint $table) {
            $table->id();
            $table->string('park_name');
            $table->longText('address');
            $table->string('state');
            $table->string('zone')->nullable();
            $table->string('originating_route')->nullable();
            $table->string('terminating_route')->nullable();
            $table->string('estimated_trip')->nullable();
            $table->string('key_man')->nullable();
            $table->string('estimated_distance')->nullable();
            $table->string('estimated_time')->nullable();
            $table->string('cost_of_transportation')->nullable();
            $table->string('road_safety_rating')->nullable();
            $table->string('field_officer')->nullable();
            $table->json('occasioned_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_route_management');
    }
};
