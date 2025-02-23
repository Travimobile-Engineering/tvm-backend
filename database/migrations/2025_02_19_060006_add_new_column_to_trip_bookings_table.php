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
        Schema::table('trip_bookings', function (Blueprint $table) {
            $table->string('manifest_status')->nullable()->after('status');
        });

        Schema::table('manifests', function (Blueprint $table) {
            $table->dropColumn('booking_id');
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('email');
            $table->dropColumn('phone_number');
            $table->dropColumn('next_of_kin');
            $table->dropColumn('next_of_kin_phone');
            $table->dropColumn('seat');

            $table->string('status')->nullable()->after('trip_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_bookings', function (Blueprint $table) {
            $table->dropColumn('manifest_status');
        });

        Schema::table('manifests', function (Blueprint $table) {
            $table->string('booking_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->string('seat')->nullable();
            $table->dropColumn('status');
        });
    }
};
