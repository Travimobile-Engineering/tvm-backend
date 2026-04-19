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
            $table->foreignId('airline_id')->nullable()->constrained();
        });

        Schema::table('airlines', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('manifest_submission_method')->nullable();
            $table->string('role')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['airline_id']);
        });

        Schema::table('airlines', function (Blueprint $table) {
            $table->dropColumn(['name', 'manifest_submission_method', 'role']);
        });
    }
};
