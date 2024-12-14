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
        Schema::create('cms_contact_us', function (Blueprint $table) {
            $table->id();
            $table->string(column: 'title');
            $table->string(column: 'subtitle');
            $table->string('map_key')->nullable();
            $table->boolean(column: 'status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_contact_us');
    }
};