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
        Schema::table('trip_schedules', function (Blueprint $table) {
            $table->bigInteger('user_id')->after('id');
        });

        Schema::table('unavailable_dates', function (Blueprint $table) {
            $table->bigInteger('user_id')->after('id');
        });
    }
};
