<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npis_ranks', function (Blueprint $table): void {
            $table->string('code', 16)->primary();
            $table->string('name');
            $table->string('abbreviation', 16);
            $table->string('category', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('npis_states', function (Blueprint $table): void {
            $table->string('code', 8)->primary();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('npis_commands', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('state_code', 8);
            $table->string('name');
            $table->string('type', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('state_code')->references('code')->on('npis_states')->cascadeOnUpdate()->restrictOnDelete();
            $table->index(['state_code', 'sort_order']);
        });

        Schema::create('npis_units', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('command_id', 64);
            $table->string('name');
            $table->string('type', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('command_id')->references('id')->on('npis_commands')->cascadeOnUpdate()->restrictOnDelete();
            $table->index(['command_id', 'sort_order']);
        });

        Schema::create('npis_airports', function (Blueprint $table): void {
            $table->string('code', 8)->primary();
            $table->string('name');
            $table->string('city');
            $table->string('state_code', 8)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('state_code')->references('code')->on('npis_states')->nullOnDelete();
        });

        Schema::create('npis_banks', function (Blueprint $table): void {
            $table->string('code', 16)->primary();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('npis_protocol_zones', function (Blueprint $table): void {
            $table->string('code', 8)->primary();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('npis_protocol_categories', function (Blueprint $table): void {
            $table->string('code', 4)->primary();
            $table->string('name');
            $table->string('members');
            $table->string('color', 16);
            $table->string('text_color', 16);
            $table->boolean('photo_required')->default(false);
            $table->boolean('reserved')->default(false);
            $table->json('zone_access');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npis_protocol_categories');
        Schema::dropIfExists('npis_protocol_zones');
        Schema::dropIfExists('npis_banks');
        Schema::dropIfExists('npis_airports');
        Schema::dropIfExists('npis_units');
        Schema::dropIfExists('npis_commands');
        Schema::dropIfExists('npis_states');
        Schema::dropIfExists('npis_ranks');
    }
};
