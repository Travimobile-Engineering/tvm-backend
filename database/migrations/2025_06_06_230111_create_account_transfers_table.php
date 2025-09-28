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
        if (! Schema::hasTable('account_transfers')) {
            Schema::create('account_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->string('reference')->unique()->nullable();
                $table->string('transfer_code')->nullable();
                $table->json('response')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
    }
};
