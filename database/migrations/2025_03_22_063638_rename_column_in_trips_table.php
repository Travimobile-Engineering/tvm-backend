<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE trips MODIFY COLUMN status ENUM('active', 'completed', 'in-progress', 'cancelled', 'upcoming')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE trips MODIFY COLUMN status ENUM('active', 'completed', 'in-progress', 'cancelled')");
        }
    }
};
