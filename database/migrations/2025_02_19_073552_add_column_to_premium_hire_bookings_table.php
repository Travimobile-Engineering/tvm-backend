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
        Schema::table('premium_hire_bookings', function (Blueprint $table) {
            $table->dateTime('start_trip_date')->nullable()->after('status');
            $table->dateTime('end_trip_date')->nullable()->after('start_trip_date');
        });

        Schema::table('premium_hire_ratings', function (Blueprint $table) {
            $table->bigInteger('vehicle_id')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('premium_hire_bookings', function (Blueprint $table) {
            $table->dropColumn('start_trip_date');
            $table->dropColumn('end_trip_date');
        });

        Schema::table('premium_hire_ratings', function (Blueprint $table) {
            $table->dropColumn('vehicle_id');
        });
    }
};
