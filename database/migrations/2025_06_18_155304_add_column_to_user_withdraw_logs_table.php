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
        Schema::table('user_withdraw_logs', function (Blueprint $table) {
            $table->decimal('previous_balance', 10, 2)->default(0)->after('amount');
            $table->decimal('new_balance', 10, 2)->default(0)->after('previous_balance');
            $table->string('reference')->nullable()->after('new_balance');
            $table->longText('response')->nullable()->after('transfer_code');
            $table->string('transfer_code')->nullable()->change();
            $table->longText('data')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_withdraw_logs', function (Blueprint $table) {
            $table->dropColumn('previous_balance');
            $table->dropColumn('new_balance');
            $table->dropColumn('reference');
            $table->dropColumn('response');
            $table->string('transfer_code')->change();
            $table->longText('data')->change();
        });
    }
};
