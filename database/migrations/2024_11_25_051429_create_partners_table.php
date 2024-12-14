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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('companyName');
            $table->string('firstName');
            $table->string('lastName');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('partnerType');
            $table->string('state');
            $table->string('location');
            $table->string('town');
            $table->string('street');
            $table->string('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};