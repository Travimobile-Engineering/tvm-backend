<?php

use App\Enum\UserType;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('user_category')->default(UserType::PASSENGER);
            $table->string('agent_id')->nullable();
            $table->string('password')->nullable();
            $table->double('wallet')->default(0.00);
            $table->string('txn_pin', 6)->default('0000');
            $table->string('address')->nullable();
            $table->string('gender')->nullable();
            $table->string('nin')->nullable();
            $table->string('profile_photo_url')->nullable();
            $table->string('next_of_kin_full_name')->nullable();
            $table->string('next_of_kin_phone_number')->nullable();
            $table->string('next_of_kin_gender')->nullable();

            $table->unsignedTinyInteger('email_verified')->default(0);
            $table->unsignedTinyInteger('sms_verified')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_code', 6);
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
