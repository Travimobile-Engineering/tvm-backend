<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emf_manifests', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('manifest_number', 40)->unique();
            $table->string('mode', 16);
            $table->string('operator');
            $table->string('vehicle_registration')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('route_origin');
            $table->string('route_destination');
            $table->unsignedInteger('seats')->nullable();
            $table->unsignedInteger('passenger_count')->default(0);
            $table->unsignedInteger('checked_count')->default(0);
            $table->string('duty_point_id', 64)->nullable();
            $table->string('state_code', 8)->nullable();
            $table->string('lga_id', 64)->nullable();
            $table->string('verdict', 16)->default('unverified');
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('expected_arrival_at')->nullable();
            $table->string('checked_by_officer_id', 64)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->string('source', 24)->default('manual');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('duty_point_id')->references('id')->on('emf_duty_points')->nullOnDelete();
            $table->foreign('state_code')->references('code')->on('emf_states')->nullOnDelete();
            $table->foreign('lga_id')->references('id')->on('emf_lgas')->nullOnDelete();
            $table->foreign('checked_by_officer_id')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['state_code', 'lga_id', 'mode', 'verdict']);
            $table->index('checked_at');
        });

        Schema::create('emf_manifest_passengers', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('manifest_id', 64);
            $table->string('full_name');
            $table->string('nin', 32)->nullable();
            $table->string('seat', 16)->nullable();
            $table->string('verdict', 16)->default('unverified');
            $table->string('watchlist_entry_id', 64)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->unsignedTinyInteger('identity_confidence')->nullable();
            $table->timestamps();

            $table->foreign('manifest_id')->references('id')->on('emf_manifests')->cascadeOnDelete();
            $table->index(['manifest_id', 'verdict']);
            $table->index('watchlist_entry_id');
        });

        Schema::create('emf_manifest_checks', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('manifest_id', 64);
            $table->string('type', 40);
            $table->string('status', 16);
            $table->string('result')->nullable();
            $table->string('performed_by', 64)->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->foreign('manifest_id')->references('id')->on('emf_manifests')->cascadeOnDelete();
            $table->foreign('performed_by')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['manifest_id', 'type', 'status']);
        });

        Schema::create('emf_watchlist_entries', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('entry_number', 40)->unique();
            $table->string('type', 24);
            $table->string('risk', 16);
            $table->string('reason');
            $table->string('source');
            $table->string('full_name')->nullable();
            $table->string('nin', 32)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('plate_number', 24)->nullable();
            $table->date('dob')->nullable();
            $table->string('nationality', 64)->default('Nigerian');
            $table->string('address')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 16)->default('active');
            $table->text('notes')->nullable();
            $table->string('created_by', 64)->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['type', 'risk', 'status']);
            $table->index('nin');
            $table->index('plate_number');
        });

        Schema::create('emf_watchlist_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('entry_id', 64);
            $table->string('name');
            $table->text('file_url');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('uploaded_by', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('entry_id')->references('id')->on('emf_watchlist_entries')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('emf_officers')->nullOnDelete();
        });

        Schema::create('emf_watchlist_subscriptions', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('subscriber_name');
            $table->string('agency');
            $table->string('scope');
            $table->json('modes');
            $table->boolean('notify_email')->default(true);
            $table->string('officer_id', 64)->nullable();
            $table->string('status', 16)->default('active');
            $table->string('created_by', 64)->nullable();
            $table->timestamps();

            $table->foreign('officer_id')->references('id')->on('emf_officers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['agency', 'status']);
        });

        Schema::create('emf_alerts', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('alert_number', 40)->unique();
            $table->string('type', 16);
            $table->string('title');
            $table->string('summary')->nullable();
            $table->string('subject_type', 24)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('manifest_id', 64)->nullable();
            $table->string('watchlist_entry_id', 64)->nullable();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->string('mode', 16)->nullable();
            $table->string('route')->nullable();
            $table->string('duty_point_id', 64)->nullable();
            $table->string('state_code', 8)->nullable();
            $table->string('lga_id', 64)->nullable();
            $table->string('status', 24)->default('open');
            $table->unsignedInteger('sla_seconds')->default(120);
            $table->timestamp('detected_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('assigned_officer_id', 64)->nullable();
            $table->string('responder_name')->nullable();
            $table->text('resolution')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('manifest_id')->references('id')->on('emf_manifests')->nullOnDelete();
            $table->foreign('watchlist_entry_id')->references('id')->on('emf_watchlist_entries')->nullOnDelete();
            $table->foreign('duty_point_id')->references('id')->on('emf_duty_points')->nullOnDelete();
            $table->foreign('assigned_officer_id')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['status', 'type', 'detected_at']);
            $table->index(['state_code', 'lga_id']);
        });

        Schema::create('emf_alert_actions', function (Blueprint $table): void {
            $table->id();
            $table->string('alert_id', 64);
            $table->string('action', 32);
            $table->text('note')->nullable();
            $table->string('reason')->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('alert_id')->references('id')->on('emf_alerts')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['alert_id', 'created_at']);
        });

        Schema::create('emf_incidents', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('incident_number', 40)->unique();
            $table->string('title');
            $table->string('type', 40);
            $table->string('severity', 16);
            $table->string('status', 24)->default('unsig');
            $table->text('summary')->nullable();
            $table->string('manifest_id', 64)->nullable();
            $table->string('alert_id', 64)->nullable();
            $table->string('watchlist_entry_id', 64)->nullable();
            $table->string('location');
            $table->string('duty_point_id', 64)->nullable();
            $table->string('state_code', 8)->nullable();
            $table->string('lga_id', 64)->nullable();
            $table->string('reporter_name')->nullable();
            $table->string('reported_by', 64)->nullable();
            $table->string('responder_officer_id', 64)->nullable();
            $table->timestamp('reported_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('manifest_id')->references('id')->on('emf_manifests')->nullOnDelete();
            $table->foreign('alert_id')->references('id')->on('emf_alerts')->nullOnDelete();
            $table->foreign('watchlist_entry_id')->references('id')->on('emf_watchlist_entries')->nullOnDelete();
            $table->foreign('duty_point_id')->references('id')->on('emf_duty_points')->nullOnDelete();
            $table->foreign('reported_by')->references('id')->on('emf_officers')->nullOnDelete();
            $table->foreign('responder_officer_id')->references('id')->on('emf_officers')->nullOnDelete();
            $table->index(['status', 'severity', 'reported_at']);
        });

        Schema::create('emf_incident_events', function (Blueprint $table): void {
            $table->id();
            $table->string('incident_id', 64);
            $table->string('event_type', 32);
            $table->text('description');
            $table->string('actor_name')->nullable();
            $table->timestamps();

            $table->foreign('incident_id')->references('id')->on('emf_incidents')->cascadeOnDelete();
            $table->index(['incident_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emf_incident_events');
        Schema::dropIfExists('emf_incidents');
        Schema::dropIfExists('emf_alert_actions');
        Schema::dropIfExists('emf_alerts');
        Schema::dropIfExists('emf_watchlist_subscriptions');
        Schema::dropIfExists('emf_watchlist_documents');
        Schema::dropIfExists('emf_watchlist_entries');
        Schema::dropIfExists('emf_manifest_checks');
        Schema::dropIfExists('emf_manifest_passengers');
        Schema::dropIfExists('emf_manifests');
    }
};
