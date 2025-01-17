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
        Schema::create('transit_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('reg_no')->nullable();
            $table->string('url')->nullable();
            $table->string('email')->nullable();
            $table->string('country_code', 5)->default('NG');
            $table->string('state')->nullable();
            $table->string('lga')->nullable();
            $table->string('phone', 14);
            $table->string('address')->nullable();
            $table->string('about_details')->nullable();
            $table->foreignId('union_id')->nullable()->constrained('transit_company_unions')->onDelete('cascade');
            $table->foreignId('union_states_chapter')->constrained('states');
            $table->unsignedTinyInteger('ev')->default(0);
            $table->unsignedTinyInteger('sv')->default(0);
            $table->string('ver_code')->nullable();
            $table->timestamp('ver_code_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transit_companies');
    }
};
