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

        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Schema::create('vehicle_types', function(Blueprint $table){
        //     $table->id();
        //     $table->string('name')->comment('eg Hiace Bus, Coaster Bus, Sienna')->unique();
        //     // $table->tinyInteger(column: 'seat_layout_rows');
        //     // $table->tinyInteger(column: 'seat_layout_columns');
        //     $table->timestamps();
        // });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('company_id')->constrained('transit_companies');
            $table->foreignId('brand_id')->constrained('vehicle_brands');
            $table->unsignedTinyInteger('ac')->nullable()->default(0);
            $table->string('plate_no');
            $table->string('engine_no');
            $table->string('chassis_no');
            $table->string('color');
            $table->string('model');
            $table->unsignedTinyInteger('air_conditioned')->default(0);
            $table->string('seats')->comment('An array of selectable seats');
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
