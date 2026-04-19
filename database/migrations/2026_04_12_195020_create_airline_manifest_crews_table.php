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
        Schema::create('airline_manifest_crews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_manifest_id')->constrained('airline_manifests')->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airline_manifest_crews');
    }
};
