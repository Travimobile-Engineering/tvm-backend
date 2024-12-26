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
        Schema::table('trips', function (Blueprint $table) {
            $table->renameColumn('trip_id', 'uuid');
            $table->bigInteger('user_id')->after('id');
            $table->string('departure_date')->nullable();
            $table->string('departure_time')->nullable();
            $table->string('repeat_trip')->nullable();
            $table->string('reoccur_duration')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->json('trip_days')->nullable();
            $table->string('bus_type');
            $table->json('bus_stops');
            $table->enum('type', ['one-time', 'recurring'])->default('one-time');
            $table->longText('reason')->nullable();
            $table->dateTime('date_cancelled')->nullable();
            $table->enum('status', ['active', 'completed', 'in-progress', 'cancelled'])
                ->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->renameColumn('uuid', 'trip_id');
            $table->dropColumn('departure_date');
            $table->dropColumn('departure_time');
            $table->dropColumn('repeat_trip');
            $table->dropColumn('reoccur_duration');
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
            $table->dropColumn('trip_days');
            $table->dropColumn('bus_type');
            $table->dropColumn('bus_stops');
            $table->dropColumn('type');
            $table->dropColumn('reason');
            $table->dropColumn('date_cancelled');
            $table->enum('status', ['active', 'completed', 'in-progress', 'cancelled'])
                ->default('active')->change();
        });
    }
};
