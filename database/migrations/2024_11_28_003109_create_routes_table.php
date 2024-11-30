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
            $table->foreignId('route_region_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('covered_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_region_id')->constrained('route_regions');
            $table->foreignId('from_subregion_id')->constrained('route_subregions');
            $table->foreignId('to_region_id')->constrained('route_regions');
            $table->foreignId('to_subregion_id')->constrained('route_subregions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('covered_routes');
        Schema::dropIfExists('routes_regions');
        Schema::dropIfExists('routes_subregions');
    }
};
