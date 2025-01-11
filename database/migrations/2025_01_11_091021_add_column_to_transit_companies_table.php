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
        Schema::table('transit_companies', function (Blueprint $table) {
            $table->string('park')->after('union_states_chapter')->nullable();
            $table->string('type')->after('ver_code_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transit_companies', function (Blueprint $table) {
            $table->dropColumn('park');
            $table->dropColumn('type');
        });
    }
};
