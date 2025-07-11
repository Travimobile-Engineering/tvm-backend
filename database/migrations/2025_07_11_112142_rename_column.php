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
        if (Schema::hasTable('issued_item')) {
            Schema::rename('issued_item', 'issued_items');
        }

        if (!Schema::hasTable('inventory_categories')) {
            Schema::create('inventory_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('status');
                $table->timestamps();
            });
        }

        Schema::table('inventories', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('inventory_categories')->onDelete('cascade');
        });

        Schema::table('issued_items', function (Blueprint $table) {
            $table->string('serial_number')->unique()->after('inventory_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
