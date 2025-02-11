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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('is_available')->default(0);
            $table->string('lng')->nullable();
            $table->string('lat')->nullable();
        });

        Schema::dropIfExists('preferred_locations');

        Schema::table('charters', function (Blueprint $table) {
            $table->integer('number_of_vehicles')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_available');
            $table->dropColumn('lng');
            $table->dropColumn('lat');
        });
    }
};
