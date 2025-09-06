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
        Schema::create('admin_bulk_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('transfer_code')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->integer('total_transfers');
            $table->json('response')->nullable();
            $table->string('status');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('account_transfers', function (Blueprint $table) {
            $table->foreignId('admin_bulk_transfer_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_transfers', function (Blueprint $table) {
            $table->dropForeign(['admin_bulk_transfer_id']);
            $table->dropColumn('admin_bulk_transfer_id');
        });
        Schema::dropIfExists('admin_bulk_transfers');
    }
};
