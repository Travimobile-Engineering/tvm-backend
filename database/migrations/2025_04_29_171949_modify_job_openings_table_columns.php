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
        Schema::table('job_openings', function (Blueprint $table) {
            $table->json('responsibilities')->change();
            $table->json('requirement')->change();
            $table->json('offer')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->longText('responsibilities')->change();
            $table->text('requirement')->change();
            $table->tinyText('offer')->change();
        });
    }
};
