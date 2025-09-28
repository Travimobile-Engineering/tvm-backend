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
            if (! Schema::hasColumn('trips', 'to')) {
                $table->string('to')->nullable()->after('price');
            }

            if (! Schema::hasColumn('trips', 'from')) {
                $table->string('from')->nullable()->after('to');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('to');
            $table->dropColumn('from');
        });
    }
};
