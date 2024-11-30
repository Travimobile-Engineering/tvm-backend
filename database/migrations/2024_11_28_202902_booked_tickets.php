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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_id')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('transit_company_id')->constrained('transit_companies');
            $table->foreignId('from_region_id')->constrained('route_regions');
            $table->foreignId('from_subregion_id')->constrained('route_subregions');
            $table->foreignId('to_region_id')->constrained('route_regions');
            $table->foreignId('to_subregion_id')->constrained('route_subregions');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
        
        Schema::create('booked_tickets', function(Blueprint $table){
            $table->id();
            $table->string('ticket_id')->unique();
            $table->foreignId('trip_id')->constrained('trips');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('pickup_point');
            $table->string('dropoff_point');
            $table->string('selected_seat', 10)->nullable();
            $table->unsignedTinyInteger('trip_type')->default(1)->comment('1:one way, 2:round trip');
            $table->string('travelling_with')->nullable()->comment('An array of users that this user is travelling with');
            $table->double('price');
            $table->unsignedTinyInteger('paid')->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedTinyInteger('means')->default(1)->comment('1:road, 2:rail, 3:sea, 4:air');
            $table->foreignId('transit_company_id')->constrained('transit_companies');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->string('departure_date');
            $table->string('departure_time');
            $table->timestamp('estimated_arrival_time');
            $table->unsignedTinyInteger('status')->default(1)->comment('0:cancelled, 1:active, 2:complete');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropifExists('booked_tickets');
        Schema::dropifExists('trips');
    }
};
