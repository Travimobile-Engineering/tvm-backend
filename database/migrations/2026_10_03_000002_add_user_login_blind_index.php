<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Email and phone number are the login identifiers but are encrypted at
     * rest. Deterministic blind indexes are added so login and lookups still
     * match them, with a plaintext fallback for legacy rows not yet backfilled.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->char('email_hash', 64)->nullable()->after('email');
            $table->char('phone_number_hash', 64)->nullable()->after('phone_number');
            $table->index('email_hash');
            $table->index('phone_number_hash');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['email_hash']);
            $table->dropIndex(['phone_number_hash']);
            $table->dropColumn(['email_hash', 'phone_number_hash']);
        });
    }
};
