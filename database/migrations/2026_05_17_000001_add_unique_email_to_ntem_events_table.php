<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a unique constraint to the email column so that duplicate
     * registrations for the same email address are rejected at the
     * database level, acting as a last-line-of-defence beyond the
     * controller-level validation rule.
     */
    public function up(): void
    {
        Schema::table('ntem_events', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('ntem_events', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }
};
