<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'published_at']);
        });

        Schema::create('discussion_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('last_post_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'last_post_at']);
        });

        Schema::create('discussion_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')
                ->constrained('discussion_threads')->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()
                ->constrained('discussion_posts')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_removed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['thread_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_posts');
        Schema::dropIfExists('discussion_threads');
        Schema::dropIfExists('announcements');
    }
};
