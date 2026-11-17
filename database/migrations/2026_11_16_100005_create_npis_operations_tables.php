<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npis_training_sessions', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('event_id', 64);
            $table->string('title');
            $table->string('track', 24);
            $table->string('subsector', 16)->nullable();
            $table->date('session_date');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->string('room');
            $table->unsignedInteger('capacity');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_id')->references('id')->on('npis_summit_events')->cascadeOnDelete();
            $table->index(['session_date', 'track']);
        });

        Schema::create('npis_training_assignments', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('registration_id', 64);
            $table->string('session_id', 64);
            $table->timestamp('assigned_at');
            $table->foreignId('assigned_by_admin_id')->nullable()->constrained('npis_admin_users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('npis_registrations')->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('npis_training_sessions')->cascadeOnDelete();
            $table->unique(['registration_id', 'session_id']);
        });

        Schema::create('npis_check_ins', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('registration_id', 64);
            $table->string('summit_tag_number', 24);
            $table->date('event_date');
            $table->timestamp('checked_in_at');
            $table->string('method', 16);
            $table->foreignId('recorded_by_admin_id')->nullable()->constrained('npis_admin_users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('npis_registrations')->cascadeOnDelete();
            $table->unique(['registration_id', 'event_date']);
            $table->index('event_date');
            $table->index('summit_tag_number');
        });

        Schema::create('npis_certificates', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('registration_id', 64)->unique();
            $table->string('certificate_number', 40)->unique();
            $table->string('file_path')->nullable();
            $table->timestamp('issued_at');
            $table->foreignId('issued_by_admin_id')->nullable()->constrained('npis_admin_users')->nullOnDelete();
            $table->string('delivery_channel', 16);
            $table->string('delivery_status', 16)->default('queued');
            $table->unsignedSmallInteger('delivery_attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('npis_registrations')->cascadeOnDelete();
            $table->index('delivery_status');
        });

        Schema::create('npis_certificate_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('certificate_id', 64);
            $table->string('channel', 16);
            $table->string('status', 16);
            $table->timestamp('attempted_at');
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->foreign('certificate_id')->references('id')->on('npis_certificates')->cascadeOnDelete();
            $table->index(['certificate_id', 'attempted_at']);
        });

        Schema::create('npis_notifications', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('template_key', 64);
            $table->string('channel', 16);
            $table->text('recipient');
            $table->string('recipient_hash', 64)->index();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status', 16)->default('queued');
            $table->string('provider', 32)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->string('related_type', 64)->nullable();
            $table->string('related_id', 64)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('npis_export_jobs', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->foreignId('admin_user_id')->nullable()->constrained('npis_admin_users')->nullOnDelete();
            $table->json('filters');
            $table->json('fields');
            $table->string('status', 16)->default('queued');
            $table->unsignedInteger('row_count')->nullable();
            $table->string('file_path')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('npis_audit_events', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('actor_type', 32);
            $table->string('actor_id', 64)->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 32)->nullable();
            $table->string('action', 64)->index();
            $table->string('target_type', 64);
            $table->string('target_id', 64)->nullable();
            $table->text('summary');
            $table->json('metadata')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });

        Schema::create('npis_idempotency_records', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 128);
            $table->string('idempotency_key', 190);
            $table->string('method', 8);
            $table->string('path');
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('status_code');
            $table->longText('response_body')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['scope', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npis_idempotency_records');
        Schema::dropIfExists('npis_audit_events');
        Schema::dropIfExists('npis_export_jobs');
        Schema::dropIfExists('npis_notifications');
        Schema::dropIfExists('npis_certificate_deliveries');
        Schema::dropIfExists('npis_certificates');
        Schema::dropIfExists('npis_check_ins');
        Schema::dropIfExists('npis_training_assignments');
        Schema::dropIfExists('npis_training_sessions');
    }
};
