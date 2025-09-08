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
        Schema::create('withdrawal_restrictions', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->json('user_types')->nullable()->comment('Array of user types affected');
            $table->decimal('min_balance', 15, 2)->default(0)->nullable();
            $table->text('message')->nullable();
            $table->boolean('complete_block')->default(false)->comment('Completely block withdrawal regardless of balance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_restrictions');
    }
};
