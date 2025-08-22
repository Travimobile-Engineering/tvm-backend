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
        Schema::table('transport_route_management', function (Blueprint $table) {
            $table->decimal('lng', 10, 8)->nullable();
            $table->decimal('lat', 10, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transport_route_management', function (Blueprint $table) {
            $table->dropColumn('lng');
            $table->dropColumn('lat');
        });
    }
};
