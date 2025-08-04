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
        Schema::table('agent_classifications', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
            $table->decimal('reward_amount', 10, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_classifications', function (Blueprint $table) {
            $table->string('amount')->change();
            $table->dropColumn('reward_amount');
        });
    }
};
