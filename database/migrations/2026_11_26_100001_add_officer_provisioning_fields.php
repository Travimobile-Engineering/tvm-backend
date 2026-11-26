<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emf_officers', function (Blueprint $table): void {
            $table->string('invitation_token_hash', 64)->nullable()->unique();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('temporary_password_expires_at')->nullable();
            $table->string('created_by', 64)->nullable();

            $table->foreign('created_by')->references('id')->on('emf_officers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('emf_officers', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->dropUnique(['invitation_token_hash']);
            $table->dropColumn([
                'invitation_token_hash', 'invitation_expires_at', 'invited_at',
                'activated_at', 'must_change_password', 'temporary_password_expires_at', 'created_by',
            ]);
        });
    }
};
