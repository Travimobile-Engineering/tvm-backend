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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete(('cascade'));
            $table->string('title');
            $table->float('amount');
            $table->string('type', 2)->comment('CR:credit, DR:debit');
            $table->foreignId('sender_id')->nullable()->constrained('users', 'id');
            $table->foreignId('receiver_id')->nullable()->constrained('users', 'id');
            $table->string('txn_reference')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
