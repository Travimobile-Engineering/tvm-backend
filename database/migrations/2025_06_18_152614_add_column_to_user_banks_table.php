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
        Schema::table('user_banks', function (Blueprint $table) {
            $table->string('recipient_code')->nullable()->after('account_name');
            $table->json('data')->nullable()->after('recipient_code');
            $table->boolean('is_default')->default(false)->after('data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_banks', function (Blueprint $table) {
            $table->dropColumn('recipient_code');
            $table->dropColumn('data');
            $table->dropColumn('is_default');
        });
    }
};
