<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('airline_manifests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_id')->constrained('airlines')->cascadeOnDelete();
            $table->string('manifest_number')->unique();
            $table->json('routing')->nullable();
            $table->string('standby_location')->nullable();
            $table->string('aircraft_type')->nullable();
            $table->string('aircraft_registration')->nullable();
            $table->string('customer');
            $table->dateTime('planned_departure_time')->nullable();
            $table->date('flight_date');
            $table->unsignedSmallInteger('total_bag_pcs')->default(0);
            $table->decimal('total_bag_wt', 8, 2)->default(0);
            $table->unsignedSmallInteger('total_pax_count')->default(0);
            $table->decimal('total_pax_wt', 8, 2)->default(0);
            $table->unsignedSmallInteger('total_cargo_pcs')->default(0);
            $table->decimal('total_cargo_wt', 8, 2)->default(0);
            $table->decimal('total_payload_wt', 8, 2)->default(0);
            $table->string('client_rep_name')->nullable();
            $table->time('time_received')->nullable();
            $table->time('time_closed')->nullable();
            $table->string('reason_for_delay')->nullable();
            $table->string('status')->default('draft');
            $table->string('source')->default('manual');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airline_manifests');
    }
};
