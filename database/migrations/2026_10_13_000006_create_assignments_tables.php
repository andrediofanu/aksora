<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('instructions')->nullable();
            $table->string('submission_type', 10)->default('file'); // text | file | both
            $table->timestamp('due_at')->nullable();
            $table->boolean('allow_late')->default(false);
            $table->decimal('max_score', 6, 2)->default(100);
            $table->decimal('weight', 6, 2)->default(1)->comment('Bobot poin dalam rata-rata berbobot tugas');
            $table->json('rubric')->nullable();
            $table->json('allowed_extensions')->nullable();
            $table->string('status', 20)->default('draft'); // draft | published
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'status']);
            $table->index('due_at');
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')
                ->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->string('status', 20)->default('submitted'); // submitted | graded | revision | returned
            $table->decimal('score', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assignment_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
    }
};
