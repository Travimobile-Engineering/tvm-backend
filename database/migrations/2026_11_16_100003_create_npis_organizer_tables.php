<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npis_admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role', 32);
            $table->string('oidc_subject')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('npis_organizer_access_grants', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['expires_at', 'revoked_at']);
        });

        Schema::create('npis_organizer_access_attempts', function (Blueprint $table): void {
            $table->id();
            $table->string('ip', 45)->index();
            $table->boolean('succeeded')->default(false);
            $table->timestamp('attempted_at');
            $table->timestamps();
        });

        Schema::create('npis_admin_sessions', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('admin_user_id')->constrained('npis_admin_users')->cascadeOnDelete();
            $table->string('access_grant_id', 64);
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->foreign('access_grant_id')->references('id')->on('npis_organizer_access_grants')->cascadeOnDelete();
            $table->index(['expires_at', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npis_admin_sessions');
        Schema::dropIfExists('npis_organizer_access_attempts');
        Schema::dropIfExists('npis_organizer_access_grants');
        Schema::dropIfExists('npis_admin_users');
    }
};
