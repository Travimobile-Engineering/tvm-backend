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
            $table->foreignId('departure')->constrained('route_subregions', 'id')->onDelete('cascade');
            $table->foreignId('destination')->constrained('route_subregions', 'id')->onDelete('cascade');
            $table->double('price');
            $table->dateTime('departure_at');
            $table->dateTime('estimated_arrival_at');
            $table->unsignedTinyInteger('means')->default(1)->comment('1:road, 2:rail, 3:sea, 4:air');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
        
        Schema::create('trip_bookings', function(Blueprint $table){
            $table->id();
            $table->string('booking_id')->unique();
            $table->string('trip_id');
            $table->foreignId('user_id')->constrained();
            $table->unsignedTinyInteger('third_party_booking')->default(0);
            $table->string('selected_seat')->nullable();
            $table->unsignedTinyInteger('trip_type')->default(1)->comment('1:one way, 2:round trip');
            $table->string('travelling_with')->nullable()->comment('An array of users that this user is travelling with');
            $table->text('third_party_passenger_details')->nullable();
            $table->float('amount_paid')->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedTinyInteger('payment_status')->comment('0:pending, 1:confirmed, 2:cancelled');
            $table->unsignedTinyInteger('status')->default(1)->comment('0:cancelled, 1:active, 2:complete');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropifExists('trip_bookings');
        Schema::dropifExists('trips');
    }
};
