<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emf_states', function (Blueprint $table): void {
            $table->string('code', 8)->primary();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('emf_lgas', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('state_code', 8);
            $table->string('name');
            $table->timestamps();
            $table->foreign('state_code')->references('code')->on('emf_states')->cascadeOnDelete();
            $table->index(['state_code', 'name']);
        });

        Schema::create('emf_duty_points', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('state_code', 8);
            $table->string('lga_id', 64)->nullable();
            $table->string('name');
            $table->string('type', 24);
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('shift_start', 5)->default('06:00');
            $table->string('shift_end', 5)->default('18:00');
            $table->unsignedInteger('officers_count')->default(0);
            $table->unsignedInteger('grants_count')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();
            $table->foreign('state_code')->references('code')->on('emf_states')->restrictOnDelete();
            $table->foreign('lga_id')->references('id')->on('emf_lgas')->nullOnDelete();
            $table->index(['state_code', 'lga_id', 'type']);
        });

        Schema::create('emf_officers', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('service_number', 32)->unique();
            $table->string('rank', 32);
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone', 32);
            $table->string('unit');
            $table->string('state_code', 8);
            $table->string('lga_id', 64)->nullable();
            $table->string('duty_point_id', 64)->nullable();
            $table->string('role', 32);
            $table->string('status', 16)->default('off_duty');
            $table->string('language', 8)->default('en');
            $table->string('password_hash');
            $table->string('watchlist_edit_code_hash')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->unsignedInteger('checks_today')->default(0);
            $table->timestamps();
            $table->foreign('state_code')->references('code')->on('emf_states')->restrictOnDelete();
            $table->foreign('lga_id')->references('id')->on('emf_lgas')->nullOnDelete();
            $table->foreign('duty_point_id')->references('id')->on('emf_duty_points')->nullOnDelete();
            $table->index(['state_code', 'status', 'role']);
        });

        Schema::create('emf_officer_sessions', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('officer_id', 64);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();
            $table->foreign('officer_id')->references('id')->on('emf_officers')->cascadeOnDelete();
            $table->index('expires_at');
        });

        Schema::create('emf_devices', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('officer_id', 64);
            $table->string('device_name');
            $table->string('platform')->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamp('requested_at');
            $table->string('approved_by', 64)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('last_seen_ip', 45)->nullable();
            $table->timestamps();
            $table->foreign('officer_id')->references('id')->on('emf_officers')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['status', 'requested_at']);
        });

        Schema::create('emf_grant_templates', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('scope');
            $table->json('modes');
            $table->unsignedSmallInteger('duration_hours');
            $table->unsignedSmallInteger('access_count')->default(1);
            $table->string('status', 16)->default('active');
            $table->string('created_by', 64)->nullable();
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('emf_officers')->nullOnDelete();
        });

        Schema::create('emf_access_grants', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('grant_number', 40)->unique();
            $table->string('officer_id', 64);
            $table->string('template_id', 64)->nullable();
            $table->string('scope');
            $table->json('modes');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 16)->default('active');
            $table->string('created_by', 64)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('officer_id')->references('id')->on('emf_officers')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('emf_grant_templates')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['scope', 'status', 'ends_at']);
        });

        Schema::create('emf_duty_roster', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('duty_point_id', 64);
            $table->date('roster_date');
            $table->string('shift', 24);
            $table->string('officer_id', 64)->nullable();
            $table->string('status', 16)->default('vacant');
            $table->boolean('published')->default(false);
            $table->string('created_by', 64)->nullable();
            $table->timestamps();
            $table->foreign('duty_point_id')->references('id')->on('emf_duty_points')->cascadeOnDelete();
            $table->foreign('officer_id')->references('id')->on('emf_officers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('emf_officers')->nullOnDelete();
            $table->unique(['duty_point_id', 'roster_date', 'shift']);
            $table->index(['roster_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emf_duty_roster');
        Schema::dropIfExists('emf_access_grants');
        Schema::dropIfExists('emf_grant_templates');
        Schema::dropIfExists('emf_devices');
        Schema::dropIfExists('emf_officer_sessions');
        Schema::dropIfExists('emf_officers');
        Schema::dropIfExists('emf_duty_points');
        Schema::dropIfExists('emf_lgas');
        Schema::dropIfExists('emf_states');
    }
};
