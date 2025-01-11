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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->unsignedBigInteger('user_id')->after('company_id')->nullable();
            $table->string('engine_no')->nullable()->change();
            $table->string('chassis_no')->nullable()->change();
            $table->string('type')->after('chassis_no')->nullable();
            $table->string('capacity')->after('type')->nullable();
            $table->string('year')->after('capacity')->nullable();
            $table->json('seats')->change()->comment('An array of selectable seats');
            $table->integer('seat_row')->after('seats')->nullable();
            $table->integer('seat_column')->after('seat_row')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('name');
            $table->dropColumn('user_id');
            $table->dropColumn('type');
            $table->dropColumn('capacity');
            $table->dropColumn('year');
            $table->dropColumn('seat_row');
            $table->dropColumn('seat_column');
        });
    }
};
