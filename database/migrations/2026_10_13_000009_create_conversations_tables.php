<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('direct'); // direct | group | class
            $table->foreignId('course_id')->nullable()
                ->constrained('courses')->nullOnDelete();
            $table->string('title', 150)->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'last_message_at']);
            $table->index('course_id');
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member'); // member | moderator | owner
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'last_read_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')
                ->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->foreignId('reply_to_id')->nullable()
                ->constrained('messages')->nullOnDelete();
            $table->string('type', 20)->default('text'); // text | file
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index('sender_id');
        });

        Schema::create('conversation_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')
                ->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
            $table->index('blocked_id');
        });

        Schema::create('message_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')
                ->constrained('messages')->cascadeOnDelete();
            $table->foreignId('reporter_id')
                ->constrained('users')->cascadeOnDelete();
            $table->string('reason', 500);
            $table->string('status', 20)->default('open'); // open | resolved | dismissed
            $table->foreignId('resolved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->unique(['message_id', 'reporter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reports');
        Schema::dropIfExists('conversation_blocks');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
