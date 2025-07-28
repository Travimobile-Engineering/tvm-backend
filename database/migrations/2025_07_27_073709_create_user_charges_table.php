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
        Schema::create('user_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type')->comment('Type of charge, e.g., driver charge, agent commission');
            $table->date('date');
            $table->decimal('amount', 8, 2);
            $table->string('user_category')->comment('Category of user, e.g., driver, agent');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'date'], 'user_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_charges');
    }
};
