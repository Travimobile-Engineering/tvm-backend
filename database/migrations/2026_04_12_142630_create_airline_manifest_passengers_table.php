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
        Schema::create('airline_manifest_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_manifest_id')->constrained('airline_manifests')->cascadeOnDelete();
            $table->unsignedTinyInteger('row_number');
            $table->string('name');
            $table->string('job')->nullable();
            $table->string('company')->nullable();
            $table->unsignedSmallInteger('bag_pcs')->default(0);
            $table->decimal('bag_wt', 8, 2)->default(0);
            $table->decimal('pax_wt', 8, 2)->default(0);
            $table->decimal('total_wt', 8, 2)->default(0);
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->boolean('is_special_cargo')->default(false);
            $table->string('special_cargo_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airline_manifest_passengers');
    }
};
