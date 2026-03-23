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
        Schema::create('airline_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_id')->constrained('airlines')->cascadeOnDelete();
            $table->string('name')->default('Default Key')->comment('Friendly label');
            $table->string('environment', 12)->comment('e.g. test, production');
            $table->string('public_key', 64)->unique();
            $table->string('secret_key_hash', 255);
            $table->string('secret_key_prefix', 20)->comment('e.g. sk_test_');
            $table->string('secret_key_hint', 8)->comment('Last 6 chars of the raw secret');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->ipAddress('last_used_ip')->nullable();
            $table->unsignedBigInteger('request_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['airline_id', 'environment']);
            $table->index(['public_key', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airline_api_keys');
    }
};
