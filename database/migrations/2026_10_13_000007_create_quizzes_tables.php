<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->string('show_results', 20)->default('immediately'); // immediately | after_deadline | never
            $table->boolean('shuffle_questions')->default(false);
            $table->decimal('pass_score', 6, 2)->default(70);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'status']);
            $table->index('opens_at');
            $table->index('closes_at');
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')
                ->constrained('quizzes')->cascadeOnDelete();
            $table->string('type', 20)->default('multiple_choice'); // multiple_choice | short_answer
            $table->text('question');
            $table->decimal('points', 6, 2)->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('correct_answer', 300)->nullable()->comment('Kunci jawaban pilihan ganda disimpan di quiz_options.is_correct');
            $table->boolean('case_sensitive')->default(false);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
        });

        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')
                ->constrained('quiz_questions')->cascadeOnDelete();
            $table->string('option_text', 500);
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')
                ->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_no')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress | submitted | graded
            $table->boolean('needs_manual_review')->default(false);
            $table->timestamps();

            $table->unique(['quiz_id', 'user_id', 'attempt_no']);
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')
                ->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')
                ->constrained('quiz_questions')->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()
                ->constrained('quiz_options')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 6, 2)->default(0);
            $table->boolean('requires_review')->default(false);
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index('question_id');
        });

        Schema::create('grade_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->cascadeOnDelete();
            $table->string('category', 20); // assignment | quiz
            $table->decimal('weight', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_weights');
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
