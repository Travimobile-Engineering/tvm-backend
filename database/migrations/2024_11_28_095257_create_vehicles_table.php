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
        
        Schema::create('vehicle_brands', function(Blueprint $table){
            $table->id();
            $table->string('brand_name');
            $table->timestamps();
        });
        
        Schema::create('vehicle_types', function(Blueprint $table){
            $table->id();
            $table->string('name')->comment('eg Hiace Bus, Coaster Bus, Sienna');
            $table->timestamps();
        });
        
        Schema::create('seat_layouts', function(Blueprint $table){
            $table->id();
            $table->string('name')->nullable();
            $table->tinyInteger('no_of_seats');
            $table->string('layout')->comment('eg: 4X5');
            $table->string('applicable_to')->comment('An array of all vehicle types that this layout is applicable to');
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('vehicle_brand_id')->constrained('vehicle_brands');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types');
            $table->string('plate_no');
            $table->string('engine_no');
            $table->string('chassis_no');
            $table->string('color');
            $table->foreignId('seat_layout_id')->constrained('seat_layouts');
            $table->string('seats')->comment('An array of selectable seats');
            // $table->unsignedInteger('from_route');
            // $table->unsignedInteger('to_route');
            $table->unsignedTinyInteger('status')->default(1)->comment('1:available, 0:unavailable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('vehicle_brands');
        Schema::dropIfExists('vehicle_types');
        Schema::dropIfExists('seat_layouts');
    }
};
