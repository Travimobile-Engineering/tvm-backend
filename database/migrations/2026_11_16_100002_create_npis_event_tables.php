<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npis_summit_events', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->string('venue_name');
            $table->string('venue_address');
            $table->string('theme')->nullable();

            $table->string('welcome_message_title')->nullable();
            $table->string('welcome_message_author')->nullable();
            $table->string('welcome_message_author_title')->nullable();
            $table->json('welcome_message_paragraphs')->nullable();
            $table->timestamp('welcome_message_approved_at')->nullable();

            $table->string('brochure_title')->nullable();
            $table->string('brochure_file_name')->nullable();
            $table->string('brochure_path')->nullable();
            $table->string('brochure_url')->nullable();
            $table->unsignedBigInteger('brochure_size_bytes')->nullable();
            $table->string('brochure_version', 32)->nullable();
            $table->timestamp('brochure_published_at')->nullable();

            $table->json('deadlines');
            $table->json('policies');
            $table->timestamps();
        });

        Schema::create('npis_faqs', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('event_id', 64);
            $table->string('question', 200);
            $table->text('answer');
            $table->string('category', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('published')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_id')->references('id')->on('npis_summit_events')->cascadeOnDelete();
            $table->index(['event_id', 'published', 'sort_order']);
        });

        Schema::create('npis_hotels', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('name');
            $table->json('room_types')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('npis_organizer_access', function (Blueprint $table): void {
            $table->id();
            $table->string('code_hash');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('npis_notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('channel', 16)->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npis_notification_templates');
        Schema::dropIfExists('npis_organizer_access');
        Schema::dropIfExists('npis_hotels');
        Schema::dropIfExists('npis_faqs');
        Schema::dropIfExists('npis_summit_events');
    }
};
