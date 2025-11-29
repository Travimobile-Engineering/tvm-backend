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
        if (! Schema::hasTable('watch_lists')) {
            Schema::create('watch_lists', function (Blueprint $table) {
                $table->id();
                $table->string('full_name');
                $table->string('phone');
                $table->string('email');
                $table->string('dob');
                $table->string('state_of_origin');
                $table->string('nin');
                $table->string('investigation_officer');
                $table->string('io_contact_number');
                $table->string('alert_location');
                $table->string('photo_url')->nullable();
                $table->string('documents')->nullable();
                $table->enum('status', ['active', 'closed', 'in custody'])->default('active');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('watch_lists')) {
            Schema::dropIfExists('watch_lists');
        }
    }
};
