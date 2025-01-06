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

        Schema::create('route_regions', function(Blueprint $table){
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('route_subregions', function(Blueprint $table){
            $table->id();
            $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
            $table->string('name');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('covered_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_region_id')->constrained('states');
            $table->foreignId('from_subregion_id')->constrained('route_subregions');
            $table->foreignId('to_region_id')->constrained('states');
            $table->foreignId('to_subregion_id')->constrained('route_subregions');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('covered_routes');
        Schema::dropIfExists('route_regions');
        Schema::dropIfExists('route_subregions');
    }
};
