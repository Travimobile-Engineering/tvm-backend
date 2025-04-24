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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->string('dob');
            $table->string('gender');
            $table->string('state_of_origin');
            $table->string('address');
            $table->string('phone');
            $table->string('email');
            $table->string('state_applying_for');
            $table->string('highest_level_of_education')->nullable();
            $table->string('field_of_study')->nullable();
            $table->string('resume_url')->nullable();
            $table->string('cover_letter_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
