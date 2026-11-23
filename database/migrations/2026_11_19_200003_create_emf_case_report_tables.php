<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emf_cases', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('case_number', 40)->unique();
            $table->string('title');
            $table->string('incident_id', 64)->nullable();
            $table->string('status', 24)->default('open');
            $table->string('severity', 16);
            $table->string('owner_officer_id', 64)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->date('due_date')->nullable();
            $table->text('summary')->nullable();
            $table->string('approval_status', 24)->default('none');
            $table->string('approved_by', 64)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('share_status', 24)->default('none');
            $table->json('linked_records')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('owner_officer_id')->references('id')->on('emf_officers')->nullOnDelete();
            $table->foreign('incident_id')->references('id')->on('emf_incidents')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['status', 'severity', 'opened_at']);
        });

        Schema::create('emf_case_events', function (Blueprint $table): void {
            $table->id();
            $table->string('case_id', 64);
            $table->string('action', 40);
            $table->text('note')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
            $table->foreign('case_id')->references('id')->on('emf_cases')->cascadeOnDelete();
            $table->index(['case_id', 'created_at']);
        });

        Schema::create('emf_case_evidence', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('case_id', 64);
            $table->string('evidence_type', 32);
            $table->string('label');
            $table->text('file_url');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('uploaded_by', 64)->nullable();
            $table->timestamp('uploaded_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('case_id')->references('id')->on('emf_cases')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('emf_officers')->nullOnDelete();
        });

        Schema::create('emf_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id', 64)->nullable();
            $table->string('actor_name');
            $table->string('action', 64);
            $table->string('target_type', 40);
            $table->string('target_id', 64)->nullable();
            $table->text('detail')->nullable();
            $table->string('result', 16)->default('done');
            $table->string('ip', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->foreign('actor_id')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['action', 'result', 'occurred_at']);
        });

        Schema::create('emf_notifications', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('officer_id', 64);
            $table->string('type', 24);
            $table->string('title');
            $table->text('body');
            $table->boolean('needs_action')->default(false);
            $table->string('status', 16)->default('unread');
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->foreign('officer_id')->references('id')->on('emf_officers')->cascadeOnDelete();
            $table->index(['officer_id', 'status', 'needs_action']);
        });

        Schema::create('emf_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->string('officer_id', 64)->unique();
            $table->json('preferences');
            $table->timestamps();
            $table->foreign('officer_id')->references('id')->on('emf_officers')->cascadeOnDelete();
        });

        Schema::create('emf_export_jobs', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('requested_by', 64)->nullable();
            $table->string('report_type', 40);
            $table->json('filters')->nullable();
            $table->string('format', 8)->default('csv');
            $table->string('status', 16)->default('queued');
            $table->text('file_url')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->boolean('watermark')->default(false);
            $table->boolean('requires_2fa')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->foreign('requested_by')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });

        Schema::create('emf_report_schedules', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('name');
            $table->string('report_type', 40);
            $table->json('filters')->nullable();
            $table->string('frequency', 24);
            $table->json('recipients');
            $table->timestamp('next_run_at');
            $table->boolean('active')->default(true);
            $table->string('created_by', 64)->nullable();
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('emf_officers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emf_report_schedules');
        Schema::dropIfExists('emf_export_jobs');
        Schema::dropIfExists('emf_notification_preferences');
        Schema::dropIfExists('emf_notifications');
        Schema::dropIfExists('emf_audit_events');
        Schema::dropIfExists('emf_case_evidence');
        Schema::dropIfExists('emf_case_events');
        Schema::dropIfExists('emf_cases');
    }
};
