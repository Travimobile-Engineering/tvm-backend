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
        Schema::create('tx_replay_guards', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128)->unique(); // fingerprint
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('route_sig', 191);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tx_replay_guards');
    }
};
