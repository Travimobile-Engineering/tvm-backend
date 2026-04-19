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
        Schema::table('ip_whitelists', function (Blueprint $table) {
            $table->foreignId('airline_id')->nullable()
                ->after('id')
                ->constrained('airlines')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_whitelists', function (Blueprint $table) {
            $table->dropColumn('airline_id');
        });
    }
};
