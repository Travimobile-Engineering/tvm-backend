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
        Schema::create('airline_manifest_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_manifest_id')->constrained('airline_manifests')->cascadeOnDelete();
            $table->unsignedTinyInteger('row_number')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('pcs')->default(0);
            $table->string('company')->nullable();
            $table->decimal('weight', 8, 2)->default(0);
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airline_manifest_cargos');
    }
};
