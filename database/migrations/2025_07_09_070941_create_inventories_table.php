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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description');
            $table->string('serial_number');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('issued_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories');
            $table->string('issued_to');
            $table->integer('item_id');
            $table->string('issues_date');
            $table->string('purpose');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('item_count', function (Blueprint $table) {
            $table->id();
            $table->integer('item_id');
            $table->integer('qty');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
