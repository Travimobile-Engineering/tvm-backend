<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE trips MODIFY COLUMN status ENUM('active', 'completed', 'in-progress', 'cancelled', 'upcoming')");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("ALTER TABLE trips MODIFY COLUMN status ENUM('active', 'completed', 'in-progress', 'cancelled')");
    }
};
