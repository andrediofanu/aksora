<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('topic', 120)->nullable();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('external_url', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft'); // draft | published | scheduled
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'status']);
            $table->index(['course_id', 'topic']);
            $table->index('published_at');
        });

        Schema::create('material_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_id')
                ->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['learning_material_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_completions');
        Schema::dropIfExists('learning_materials');
    }
};
