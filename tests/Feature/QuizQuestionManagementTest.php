<?php

namespace Tests\Feature;

use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * CRUD soal kuis: hanya guru pengampu/pemilik kuis yang boleh,
 * termasuk penyimpanan kunci jawaban & case_sensitive (regresi error 500
 * "Undefined variable $request" pada penyimpanan soal).
 */
class QuizQuestionManagementTest extends TestCase
{
    use BuildsCourses, RefreshDatabase;

    public function test_teacher_can_add_multiple_choice_question(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $quiz = $this->makeQuiz($course, $teacher);

        $this->actingAs($teacher)
            ->post("/kelas/{$course->id}/kuis/{$quiz->id}/soal", [
                'type' => 'multiple_choice',
                'question' => 'Berapa hasil 2 + 3?',
                'points' => 10,
                'options' => ['4', '5', '6', '7'],
                'correct_option' => 1,
            ])
            ->assertSessionHas('success');

        $question = QuizQuestion::where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame('multiple_choice', $question->type);
        $this->assertNull($question->correct_answer, 'Soal pilihan ganda tidak menyimpan kunci teks.');
        $this->assertSame(1, $question->sort_order);

        $options = $question->options()->orderBy('sort_order')->get();
        $this->assertCount(4, $options);
        $this->assertSame('5', $options->firstWhere('is_correct', true)?->option_text);
        $this->assertSame(3, $options->where('is_correct', false)->count());
    }

    public function test_teacher_can_add_short_answer_question_with_case_sensitivity(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $quiz = $this->makeQuiz($course, $teacher);

        $this->actingAs($teacher)
            ->post("/kelas/{$course->id}/kuis/{$quiz->id}/soal", [
                'type' => 'short_answer',
                'question' => 'Ibu kota Indonesia?',
                'points' => 5,
                'correct_answer' => 'Jakarta',
                'case_sensitive' => '1',
            ])
            ->assertSessionHas('success');

        $question = QuizQuestion::where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame('short_answer', $question->type);
        $this->assertSame('Jakarta', $question->correct_answer);
        $this->assertTrue((bool) $question->case_sensitive, 'Case sensitivity harus tersimpan.');
        $this->assertSame(0, $question->options()->count());
    }

    public function test_question_store_validates_required_fields(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $quiz = $this->makeQuiz($course, $teacher);

        $this->actingAs($teacher)
            ->post("/kelas/{$course->id}/kuis/{$quiz->id}/soal", [
                'type' => 'multiple_choice',
                'question' => '',
                'points' => 0,
                'options' => ['satu'],
            ])
            ->assertSessionHasErrors(['question', 'points', 'options', 'correct_option']);

        $this->assertSame(0, $quiz->questions()->count());
    }

    public function test_teacher_can_update_question_type_and_case_sensitivity(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $quiz = $this->makeQuiz($course, $teacher);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'short_answer',
            'question' => 'Sebutkan ibu kota!',
            'points' => 5,
            'sort_order' => 1,
            'correct_answer' => 'jakarta',
            'case_sensitive' => false,
        ]);

        $this->actingAs($teacher)
            ->put("/kelas/{$course->id}/kuis/{$quiz->id}/soal/{$question->id}", [
                'type' => 'short_answer',
                'question' => 'Sebutkan ibu kota Indonesia!',
                'points' => 10,
                'correct_answer' => 'Jakarta',
                'case_sensitive' => '1',
            ])
            ->assertSessionHas('success');

        $question->refresh();
        $this->assertSame('Sebutkan ibu kota Indonesia!', $question->question);
        $this->assertSame('Jakarta', $question->correct_answer);
        $this->assertTrue((bool) $question->case_sensitive);
        $this->assertSame(10.0, (float) $question->points);
    }

    public function test_student_cannot_add_question(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $quiz = $this->makeQuiz($course, $teacher);
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $this->actingAs($student)
            ->post("/kelas/{$course->id}/kuis/{$quiz->id}/soal", [
                'type' => 'multiple_choice',
                'question' => 'Soal curian?',
                'points' => 10,
                'options' => ['A', 'B'],
                'correct_option' => 0,
            ])
            ->assertForbidden();

        $this->assertSame(0, $quiz->questions()->count());
    }

    public function test_another_teacher_cannot_add_question_to_someone_elses_quiz(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $quiz = $this->makeQuiz($course, $teacher);
        $intruder = User::factory()->teacher()->create();

        $this->actingAs($intruder)
            ->post("/kelas/{$course->id}/kuis/{$quiz->id}/soal", [
                'type' => 'multiple_choice',
                'question' => 'Soal orang lain?',
                'points' => 10,
                'options' => ['A', 'B'],
                'correct_option' => 0,
            ])
            ->assertForbidden();

        $this->assertSame(0, $quiz->questions()->count());
    }

    public function test_teacher_can_delete_question(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $quiz = $this->makeQuiz($course, $teacher);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'short_answer',
            'question' => 'Hapus saya.',
            'points' => 5,
            'sort_order' => 1,
            'correct_answer' => 'ya',
        ]);

        $this->actingAs($teacher)
            ->delete("/kelas/{$course->id}/kuis/{$quiz->id}/soal/{$question->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('quiz_questions', ['id' => $question->id]);
    }
}
