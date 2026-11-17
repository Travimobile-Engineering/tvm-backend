<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('npis_badges', function (Blueprint $table): void {
            $table->text('photo_url')->nullable();
            $table->string('photo_public_id')->nullable()->index();
        });

        Schema::table('npis_certificates', function (Blueprint $table): void {
            $table->text('file_url')->nullable();
            $table->string('file_public_id')->nullable()->index();
        });

        Schema::table('npis_export_jobs', function (Blueprint $table): void {
            $table->text('file_url')->nullable();
            $table->string('file_public_id')->nullable()->index();
        });

        Schema::table('npis_summit_events', function (Blueprint $table): void {
            $table->string('brochure_public_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('npis_summit_events', function (Blueprint $table): void {
            $table->dropColumn('brochure_public_id');
        });

        Schema::table('npis_export_jobs', function (Blueprint $table): void {
            $table->dropColumn(['file_url', 'file_public_id']);
        });

        Schema::table('npis_certificates', function (Blueprint $table): void {
            $table->dropColumn(['file_url', 'file_public_id']);
        });

        Schema::table('npis_badges', function (Blueprint $table): void {
            $table->dropColumn(['photo_url', 'photo_public_id']);
        });
    }
};
