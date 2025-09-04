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
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('rows');
            $table->integer('columns');
            $table->timestamps();
        });

        if (! Schema::hasColumn('vehicles', 'vehicle_type_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->bigInteger('vehicle_type_id')->nullable();
                $table->string('manufacturer')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['vehicle_type_id', 'manufacturer']);
        });
    }
};
