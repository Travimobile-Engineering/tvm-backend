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
        Schema::table('parks', function (Blueprint $table) {
            $table->unsignedBigInteger('transit_company_id')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('opening_time')->nullable();
            $table->string('closing_time')->nullable();
            $table->string('working_days')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('park', function (Blueprint $table) {
            $table->dropColumn('transit_company_id');
            $table->dropColumn('address');
            $table->dropColumn('phone');
            $table->dropColumn('email');
            $table->dropColumn('opening_time');
            $table->dropColumn('closing_time');
            $table->dropColumn('working_days');
        });
    }
};
