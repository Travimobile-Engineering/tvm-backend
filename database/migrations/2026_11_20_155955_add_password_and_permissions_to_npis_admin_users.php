<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('npis_admin_users', function (Blueprint $table): void {
            $table->string('password')->nullable()->after('email');
            $table->json('extra_permissions')->nullable()->after('role');
        });

        // Password logins issue sessions without an organizer access grant, so
        // the grant reference must be nullable.
        Schema::table('npis_admin_sessions', function (Blueprint $table): void {
            $table->string('access_grant_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('npis_admin_sessions')->whereNull('access_grant_id')->delete();

        Schema::table('npis_admin_sessions', function (Blueprint $table): void {
            $table->string('access_grant_id', 64)->nullable(false)->change();
        });

        Schema::table('npis_admin_users', function (Blueprint $table): void {
            $table->dropColumn(['password', 'extra_permissions']);
        });
    }
};
