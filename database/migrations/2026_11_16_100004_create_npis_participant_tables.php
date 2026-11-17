<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npis_verified_contacts', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('channel', 16);
            $table->text('destination');
            $table->string('destination_hash', 64);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'destination_hash']);
            $table->index('destination_hash');
        });

        Schema::create('npis_contact_verifications', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('contact_id', 64)->nullable();
            $table->string('channel', 16);
            $table->text('destination');
            $table->string('destination_hash', 64);
            $table->string('purpose', 16)->default('registration');
            $table->string('code_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('resend_available_at');
            $table->unsignedSmallInteger('resends_remaining');
            $table->unsignedSmallInteger('attempts_remaining');
            $table->timestamp('consumed_at')->nullable();
            $table->string('delivery_status', 16)->default('queued');
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('npis_verified_contacts')->nullOnDelete();
            $table->index(['destination_hash', 'consumed_at']);
            $table->index(['ip', 'created_at']);
            $table->index('expires_at');
        });

        Schema::create('npis_participant_sessions', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('contact_id', 64);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('npis_verified_contacts')->cascadeOnDelete();
            $table->index('expires_at');
        });

        Schema::create('npis_registrations', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('event_id', 64);
            $table->string('verified_contact_id', 64);
            $table->string('active_contact_key', 64)->nullable()->unique();
            $table->string('summit_tag_number', 24)->unique();
            $table->string('status', 24)->default('incomplete');
            $table->string('source', 24)->default('online');
            $table->string('participant_type', 16);
            $table->string('protocol_category', 4)->nullable();
            $table->string('duplicate_of_registration_id', 64)->nullable();
            $table->string('merged_into_registration_id', 64)->nullable();
            $table->json('review_flags');
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_id')->references('id')->on('npis_summit_events')->restrictOnDelete();
            $table->foreign('verified_contact_id')->references('id')->on('npis_verified_contacts')->restrictOnDelete();
            $table->foreign('protocol_category')->references('code')->on('npis_protocol_categories')->nullOnDelete();
            $table->foreign('duplicate_of_registration_id')->references('id')->on('npis_registrations')->nullOnDelete();
            $table->foreign('merged_into_registration_id')->references('id')->on('npis_registrations')->nullOnDelete();
            $table->index(['event_id', 'status']);
            $table->index(['participant_type', 'status']);
            $table->index('submitted_at');
        });

        Schema::create('npis_participant_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_id', 64)->unique();
            $table->string('full_name', 200);
            $table->string('full_name_normalized', 200);
            $table->string('sector', 32)->nullable();
            $table->string('rank_code', 16)->nullable();
            $table->string('state_code', 8)->nullable();
            $table->string('command_id', 64)->nullable();
            $table->string('unit_id', 64)->nullable();
            $table->string('unit_other_name', 200)->nullable();
            $table->string('organization', 200)->nullable();
            $table->string('organization_normalized', 200)->nullable();
            $table->string('designation', 200)->nullable();
            $table->string('vendor_service', 32)->nullable();
            $table->string('vendor_service_other', 120)->nullable();
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('npis_registrations')->cascadeOnDelete();
            $table->foreign('rank_code')->references('code')->on('npis_ranks')->nullOnDelete();
            $table->foreign('state_code')->references('code')->on('npis_states')->nullOnDelete();
            $table->foreign('command_id')->references('id')->on('npis_commands')->nullOnDelete();
            $table->foreign('unit_id')->references('id')->on('npis_units')->nullOnDelete();
            $table->index('full_name_normalized');
            $table->index('organization_normalized');
            $table->index(['state_code', 'command_id']);
        });

        Schema::create('npis_itineraries', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_id', 64)->unique();
            $table->string('travel_mode', 16);
            $table->date('arrival_date')->nullable();
            $table->string('arrival_time', 5)->nullable();
            $table->string('arrival_time_window', 16)->nullable();
            $table->string('arrival_plan_status', 16);
            $table->date('departure_date')->nullable();
            $table->string('departure_time', 5)->nullable();
            $table->string('departure_time_window', 16)->nullable();
            $table->string('departure_plan_status', 16);
            $table->string('post_summit_plan', 24);
            $table->string('departure_airport_code', 8)->nullable();
            $table->string('flight_time', 5)->nullable();
            $table->string('flight_time_window', 16)->nullable();
            $table->string('flight_number', 16)->nullable();
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('npis_registrations')->cascadeOnDelete();
            $table->foreign('departure_airport_code')->references('code')->on('npis_airports')->nullOnDelete();
            $table->index(['arrival_date', 'departure_date']);
        });

        Schema::create('npis_accommodation_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_id', 64)->unique();
            $table->string('preference', 24);
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->text('special_needs')->nullable();
            $table->boolean('allowance_requested')->default(false);
            $table->string('payout_bank_code', 16)->nullable();
            $table->text('payout_account_number')->nullable();
            $table->text('payout_account_name')->nullable();
            $table->string('allocation_status', 24)->default('not_requested');
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('npis_registrations')->cascadeOnDelete();
            $table->foreign('payout_bank_code')->references('code')->on('npis_banks')->nullOnDelete();
            $table->index(['preference', 'allocation_status']);
            $table->index(['check_in_date', 'check_out_date']);
        });

        Schema::create('npis_accommodation_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('accommodation_request_id')->unique();
            $table->string('hotel_name');
            $table->string('room_type');
            $table->timestamp('confirmed_at');
            $table->foreignId('allocated_by_admin_id')->nullable()->constrained('npis_admin_users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('accommodation_request_id', 'npis_alloc_request_fk')
                ->references('id')->on('npis_accommodation_requests')->cascadeOnDelete();
        });

        Schema::create('npis_badges', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_id', 64)->unique();
            $table->string('category', 4)->nullable();
            $table->string('source', 16)->default('derived');
            $table->string('photo_path')->nullable();
            $table->string('photo_source', 16)->nullable();
            $table->timestamp('photo_uploaded_at')->nullable();
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('npis_registrations')->cascadeOnDelete();
            $table->foreign('category')->references('code')->on('npis_protocol_categories')->nullOnDelete();
        });

        Schema::create('npis_programme_interests', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_id', 64)->unique();
            $table->string('track', 24);
            $table->json('transport_subsectors');
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('npis_registrations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npis_programme_interests');
        Schema::dropIfExists('npis_badges');
        Schema::dropIfExists('npis_accommodation_allocations');
        Schema::dropIfExists('npis_accommodation_requests');
        Schema::dropIfExists('npis_itineraries');
        Schema::dropIfExists('npis_participant_profiles');
        Schema::dropIfExists('npis_registrations');
        Schema::dropIfExists('npis_participant_sessions');
        Schema::dropIfExists('npis_contact_verifications');
        Schema::dropIfExists('npis_verified_contacts');
    }
};
