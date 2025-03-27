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
        Schema::table('users', function (Blueprint $table) {
            $table->string('agent_id')->nullable()->change();
            $table->boolean('inbox_notifications')->default(false);
            $table->boolean('email_notifications')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('agent_id')->change();
            $table->dropColumn('inbox_notifications');
            $table->dropColumn('email_notifications');
        });
    }
};
