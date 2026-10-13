<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 12)->unique();
            $table->string('invite_token', 40)->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('subject_id')->nullable()
                ->constrained('subjects')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()
                ->constrained('academic_years')->nullOnDelete();
            $table->foreignId('teacher_id')
                ->constrained('users')->cascadeOnDelete();
            $table->string('grade_level', 60)->nullable();
            $table->string('room', 60)->nullable();
            $table->string('status', 20)->default('active'); // active | archived
            $table->unsignedInteger('max_members')->default(40);
            $table->boolean('allow_student_private_message')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['teacher_id', 'status']);
            $table->index('academic_year_id');
            $table->index('name');
        });

        Schema::create('course_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('student'); // teacher | student
            $table->string('status', 20)->default('active'); // active | removed
            $table->string('joined_source', 20)->default('code'); // code | invite | teacher
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['course_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_memberships');
        Schema::dropIfExists('courses');
    }
};
