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
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'recipient_code')) {
                $table->string('recipient_code')->nullable()->after('fees');
            }

            if (!Schema::hasColumn('accounts', 'data')) {
                $table->longText('data')->nullable()->after('recipient_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'recipient_code')) {
                $table->dropColumn('recipient_code');
            }

            if (Schema::hasColumn('accounts', 'data')) {
                $table->dropColumn('data');
            }
        });
    }
};
