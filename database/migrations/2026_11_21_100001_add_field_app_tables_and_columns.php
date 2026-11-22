<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addOfficerColumns();
        $this->addManifestColumns();
        $this->addPassengerColumns();
        $this->addIncidentColumns();
        $this->addAlertColumns();

        if (! Schema::hasTable('emf_field_checks')) {
            Schema::create('emf_field_checks', function (Blueprint $table): void {
                $table->string('id', 64)->primary();
                $table->string('check_reference', 40)->unique();
                $table->string('manifest_id', 64);
                $table->string('officer_id', 64);
                $table->string('duty_point_id', 64)->nullable();
                $table->string('status', 24)->default('in_progress');
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('hold_at')->nullable();
                $table->timestamp('hold_acknowledged_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->text('hold_reason')->nullable();
                $table->string('ops_alert_reference', 40)->nullable();
                $table->string('clearance_reference', 40)->nullable()->unique();
                $table->string('incident_id', 64)->nullable();
                $table->unsignedInteger('listed_count')->default(0);
                $table->unsignedInteger('confirmed_count')->default(0);
                $table->unsignedInteger('flagged_count')->default(0);
                $table->unsignedInteger('unverified_count')->default(0);
                $table->unsignedInteger('cleared_with_note_count')->default(0);
                $table->boolean('vehicle_cleared')->default(false);
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('manifest_id')->references('id')->on('emf_manifests')->cascadeOnDelete();
                $table->foreign('officer_id')->references('id')->on('emf_officers')->cascadeOnDelete();
                $table->foreign('duty_point_id')->references('id')->on('emf_duty_points')->nullOnDelete();
                $table->foreign('incident_id')->references('id')->on('emf_incidents')->nullOnDelete();
                $table->index(['officer_id', 'started_at']);
                $table->index(['manifest_id', 'status']);
            });
        }

        if (! Schema::hasTable('emf_field_check_seats')) {
            Schema::create('emf_field_check_seats', function (Blueprint $table): void {
                $table->string('id', 64)->primary();
                $table->string('field_check_id', 64);
                $table->string('manifest_passenger_id', 64);
                $table->string('seat', 16)->nullable();
                $table->string('status', 24)->default('pending');
                $table->string('reason')->nullable();
                $table->text('note')->nullable();
                $table->json('identity_snapshot')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('flagged_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->string('resolved_by', 64)->nullable();
                $table->string('alert_id', 64)->nullable();
                $table->string('incident_id', 64)->nullable();
                $table->timestamps();

                $table->foreign('field_check_id')->references('id')->on('emf_field_checks')->cascadeOnDelete();
                $table->foreign('manifest_passenger_id')->references('id')->on('emf_manifest_passengers')->cascadeOnDelete();
                $table->foreign('resolved_by')->references('id')->on('emf_officers')->nullOnDelete();
                $table->foreign('alert_id')->references('id')->on('emf_alerts')->nullOnDelete();
                $table->foreign('incident_id')->references('id')->on('emf_incidents')->nullOnDelete();
                $table->unique(['field_check_id', 'manifest_passenger_id'], 'emf_fcs_check_passenger_unique');
                $table->index(['field_check_id', 'status']);
            });
        }

        if (! Schema::hasTable('emf_field_manifest_events')) {
            Schema::create('emf_field_manifest_events', function (Blueprint $table): void {
                $table->id();
                $table->string('manifest_id', 64);
                $table->string('event_type', 32);
                $table->string('location');
                $table->timestamp('logged_at');
                $table->string('source', 24)->default('manual');
                $table->text('note')->nullable();
                $table->string('actor_id', 64)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('manifest_id')->references('id')->on('emf_manifests')->cascadeOnDelete();
                $table->foreign('actor_id')->references('id')->on('emf_officers')->nullOnDelete();
                $table->index(['manifest_id', 'logged_at']);
            });
        }

        if (! Schema::hasTable('emf_incident_attachments')) {
            Schema::create('emf_incident_attachments', function (Blueprint $table): void {
                $table->string('id', 64)->primary();
                $table->string('incident_id', 64);
                $table->string('attachment_type', 24);
                $table->text('file_url');
                $table->unsignedBigInteger('file_size')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->string('uploaded_by', 64)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('incident_id')->references('id')->on('emf_incidents')->cascadeOnDelete();
                $table->foreign('uploaded_by')->references('id')->on('emf_officers')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('emf_support_requests')) {
            Schema::create('emf_support_requests', function (Blueprint $table): void {
                $table->string('id', 64)->primary();
                $table->string('officer_id', 64);
                $table->string('request_type', 32);
                $table->string('subject');
                $table->text('message');
                $table->string('app_version')->nullable();
                $table->json('device_info')->nullable();
                $table->string('status', 24)->default('open');
                $table->timestamps();

                $table->foreign('officer_id')->references('id')->on('emf_officers')->cascadeOnDelete();
                $table->index(['officer_id', 'request_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('emf_support_requests');
        Schema::dropIfExists('emf_incident_attachments');
        Schema::dropIfExists('emf_field_manifest_events');
        Schema::dropIfExists('emf_field_check_seats');
        Schema::dropIfExists('emf_field_checks');

        if (Schema::hasTable('emf_alerts')) {
            Schema::table('emf_alerts', function (Blueprint $table): void {
                if (Schema::hasColumn('emf_alerts', 'field_outcome')) {
                    $table->dropColumn(['match_rule', 'boarding_point', 'field_outcome', 'alert_post']);
                }
            });
        }

        if (Schema::hasTable('emf_incidents')) {
            Schema::table('emf_incidents', function (Blueprint $table): void {
                if (Schema::hasColumn('emf_incidents', 'client_report_id')) {
                    $table->dropColumn(['category', 'report_state', 'client_report_id', 'signed_at', 'signed_by', 'gps_latitude', 'gps_longitude', 'linked_vehicle', 'occurred_at']);
                }
            });
        }

        if (Schema::hasTable('emf_manifest_passengers')) {
            Schema::table('emf_manifest_passengers', function (Blueprint $table): void {
                if (Schema::hasColumn('emf_manifest_passengers', 'sex')) {
                    $table->dropColumn(['sex', 'dob', 'phone', 'photo_url', 'nin_record', 'boarding_point']);
                }
            });
        }

        if (Schema::hasTable('emf_manifests')) {
            Schema::table('emf_manifests', function (Blueprint $table): void {
                if (Schema::hasColumn('emf_manifests', 'arrival_status')) {
                    $table->dropColumn(['arrival_status', 'cleared_at', 'clearance_reference']);
                }
            });
        }

        if (Schema::hasTable('emf_officers')) {
            Schema::table('emf_officers', function (Blueprint $table): void {
                if (Schema::hasColumn('emf_officers', 'nin')) {
                    $table->dropColumn(['nin', 'division', 'reports_to', 'ops_room', 'photo_url', 'password_changed_at', 'two_factor_enabled', 'two_factor_secret', 'biometric_enabled', 'theme', 'field_notification_preferences', 'backup_codes']);
                }
            });
        }
    }

    private function addOfficerColumns(): void
    {
        if (! Schema::hasTable('emf_officers')) {
            return;
        }

        Schema::table('emf_officers', function (Blueprint $table): void {
            if (! Schema::hasColumn('emf_officers', 'nin')) {
                $table->string('nin', 20)->nullable();
                $table->string('division')->nullable();
                $table->string('reports_to')->nullable();
                $table->string('ops_room')->nullable();
                $table->text('photo_url')->nullable();
                $table->timestamp('password_changed_at')->nullable();
                $table->boolean('two_factor_enabled')->default(false);
                $table->string('two_factor_secret')->nullable();
                $table->boolean('biometric_enabled')->default(false);
                $table->string('theme', 16)->default('system');
                $table->json('field_notification_preferences')->nullable();
                $table->json('backup_codes')->nullable();
            }
        });
    }

    private function addManifestColumns(): void
    {
        if (! Schema::hasTable('emf_manifests')) {
            return;
        }

        Schema::table('emf_manifests', function (Blueprint $table): void {
            if (! Schema::hasColumn('emf_manifests', 'arrival_status')) {
                $table->string('arrival_status', 24)->default('scheduled');
                $table->timestamp('cleared_at')->nullable();
                $table->string('clearance_reference', 40)->nullable();
            }

            if (! Schema::hasIndex('emf_manifests', 'emf_manifests_arrival_status_index')) {
                $table->index('arrival_status');
            }
        });
    }

    private function addPassengerColumns(): void
    {
        if (! Schema::hasTable('emf_manifest_passengers')) {
            return;
        }

        Schema::table('emf_manifest_passengers', function (Blueprint $table): void {
            if (! Schema::hasColumn('emf_manifest_passengers', 'sex')) {
                $table->string('sex', 8)->nullable();
                $table->date('dob')->nullable();
                $table->string('phone', 32)->nullable();
                $table->text('photo_url')->nullable();
                $table->json('nin_record')->nullable();
                $table->string('boarding_point')->nullable();
            }
        });
    }

    private function addIncidentColumns(): void
    {
        if (! Schema::hasTable('emf_incidents')) {
            return;
        }

        Schema::table('emf_incidents', function (Blueprint $table): void {
            if (! Schema::hasColumn('emf_incidents', 'category')) {
                $table->string('category', 64)->nullable();
                $table->string('report_state', 32)->default('open');
                $table->string('client_report_id', 80)->nullable()->unique();
                $table->timestamp('signed_at')->nullable();
                $table->string('signed_by', 64)->nullable();
                $table->decimal('gps_latitude', 10, 7)->nullable();
                $table->decimal('gps_longitude', 10, 7)->nullable();
                $table->string('linked_vehicle', 32)->nullable();
                $table->timestamp('occurred_at')->nullable();
            }

            if (! Schema::hasIndex('emf_incidents', 'emf_incidents_reported_by_report_state_index')) {
                $table->index(['reported_by', 'report_state']);
            }
        });
    }

    private function addAlertColumns(): void
    {
        if (! Schema::hasTable('emf_alerts')) {
            return;
        }

        Schema::table('emf_alerts', function (Blueprint $table): void {
            if (! Schema::hasColumn('emf_alerts', 'match_rule')) {
                $table->string('match_rule')->nullable();
                $table->string('boarding_point')->nullable();
                $table->string('field_outcome', 32)->nullable();
                $table->string('alert_post')->nullable();
            }

            if (! Schema::hasIndex('emf_alerts', 'emf_alerts_field_outcome_index')) {
                $table->index('field_outcome');
            }
        });
    }
};
