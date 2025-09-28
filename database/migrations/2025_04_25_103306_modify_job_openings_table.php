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
            $table->text('summary')->change();
            $table->longText('responsibilities')->change();
            $table->text('requirement')->change();
            $table->tinyText('offer')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {

            $table->tinyText('summary')->change();
            $table->text('responsibilities')->change();
            $table->tinyText('requirement')->change();
            $table->string('offer')->change();
        });
    }
};
