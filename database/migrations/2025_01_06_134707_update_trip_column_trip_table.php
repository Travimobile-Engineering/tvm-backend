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
            $table->dateTime('departure_at')->nullable()->change();
            $table->dateTime('estimated_arrival_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->bigInteger('transit_company_id')->nullable(false)->change();
            $table->dateTime('departure_at')->nullable(false)->change();
            $table->dateTime('estimated_arrival_at')->nullable(false)->change();
        });
    }
};
