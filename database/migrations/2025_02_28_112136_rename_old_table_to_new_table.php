<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('driver_banks', 'user_banks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('user_banks', 'driver_banks');
    }
};
