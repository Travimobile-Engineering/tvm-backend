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
        Schema::table('watch_lists', function (Blueprint $table) {
            $table->string('category')->nullable()->after('documents');
            $table->string('recent_location')->nullable()->after('category');
            $table->text('observation')->nullable()->after('recent_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watch_lists', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->dropColumn('recent_location');
            $table->dropColumn('observation');
        });
    }
};
