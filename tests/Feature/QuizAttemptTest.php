<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * Kuis: percobaan, penilaian otomatis pilihan ganda,
 * dan penyembunyian kunci jawaban dari murid.
 */
class QuizAttemptTest extends TestCase
{
    use BuildsCourses, RefreshDatabase;

    /** Buat kelas + kuis dengan satu soal pilihan ganda. */
    private function setupQuiz(): array
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $quiz = $this->makeQuiz($course, $teacher, ['max_attempts' => 2]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
            'question' => 'Berapa hasil 2 + 2?',
            'points' => 10,
            'sort_order' => 1,
        ]);

        $wrong = QuizOption::create([
            'question_id' => $question->id,
            'option_text' => '3',
            'is_correct' => false,
            'sort_order' => 1,
        ]);
        $right = QuizOption::create([
            'question_id' => $question->id,
            'option_text' => '4',
            'is_correct' => true,
            'sort_order' => 2,
        ]);

        return compact('course', 'teacher', 'student', 'quiz', 'question', 'wrong', 'right');
    }

    public function test_student_can_start_and_take_quiz(): void
    {
        ['course' => $c, 'quiz' => $quiz, 'student' => $s] = $this->setupQuiz();

        $this->actingAs($s)
            ->post("/kelas/{$c->id}/kuis/{$quiz->id}/mulai")
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $s->id,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_outsider_cannot_start_quiz(): void
    {
        ['course' => $c, 'quiz' => $quiz] = $this->setupQuiz();
        $outsider = User::factory()->student()->create();

        $this->actingAs($outsider)
            ->post("/kelas/{$c->id}/kuis/{$quiz->id}/mulai")
            ->assertForbidden();
    }

    public function test_auto_grading_of_correct_multiple_choice_answer(): void
    {
        ['course' => $c, 'quiz' => $quiz, 'student' => $s, 'question' => $q, 'right' => $right] = $this->setupQuiz();

        $attempt = $this->makeAttempt($quiz, $s);

        $this->actingAs($s)
            ->postJson("/kelas/{$c->id}/kuis/{$quiz->id}/jawab/{$attempt->id}", [
                'question_id' => $q->id,
                'option_id' => $right->id,
            ])
            ->assertOk();

        $this->actingAs($s)
            ->post("/kelas/{$c->id}/kuis/{$quiz->id}/selesai/{$attempt->id}")
            ->assertRedirect();

        $attempt->refresh();
        $this->assertSame(QuizAttempt::STATUS_GRADED, $attempt->status);
        $this->assertEquals(10.0, (float) $attempt->score);

        $answer = QuizAnswer::where('attempt_id', $attempt->id)->first();
        $this->assertTrue((bool) $answer->is_correct);
        $this->assertEquals(10.0, (float) $answer->points_earned);
    }

    public function test_wrong_answer_scores_zero(): void
    {
        ['course' => $c, 'quiz' => $quiz, 'student' => $s, 'question' => $q, 'wrong' => $wrong] = $this->setupQuiz();

        $attempt = $this->makeAttempt($quiz, $s);

        $this->actingAs($s)
            ->postJson("/kelas/{$c->id}/kuis/{$quiz->id}/jawab/{$attempt->id}", [
                'question_id' => $q->id,
                'option_id' => $wrong->id,
            ])
            ->assertOk();

        $this->actingAs($s)
            ->post("/kelas/{$c->id}/kuis/{$quiz->id}/selesai/{$attempt->id}")
            ->assertRedirect();

        $attempt->refresh();
        $this->assertEquals(0.0, (float) $attempt->score);
    }

    public function test_student_cannot_answer_someone_elses_attempt(): void
    {
        ['course' => $c, 'quiz' => $quiz, 'student' => $s, 'question' => $q] = $this->setupQuiz();
        $other = User::factory()->student()->create();
        $this->enroll($c, $other);

        $attempt = $this->makeAttempt($quiz, $s);

        $this->actingAs($other)
            ->postJson("/kelas/{$c->id}/kuis/{$quiz->id}/jawab/{$attempt->id}", [
                'question_id' => $q->id,
                'option_id' => 1,
            ])
            ->assertForbidden();
    }

    public function test_answer_key_is_not_leaked_to_student_in_take_page(): void
    {
        ['course' => $c, 'quiz' => $quiz, 'student' => $s, 'question' => $q] = $this->setupQuiz();

        $attempt = $this->makeAttempt($quiz, $s);

        $response = $this->actingAs($s)
            ->get("/kelas/{$c->id}/kuis/{$quiz->id}/ambil/{$attempt->id}");

        $response->assertOk();
        // Kunci jawaban (teks jawaban benar) tidak boleh ada di HTML.
        $response->assertDontSee('"is_correct":true');
        $response->assertDontSee('is_correct":true');
    }

    public function test_question_model_hides_correct_answer_attribute(): void
    {
        ['question' => $q] = $this->setupQuiz();
        $q->correct_answer = 'RAHASIA';

        $array = $q->toArray();

        $this->assertArrayNotHasKey('correct_answer', $array);
    }

    public function test_quiz_option_hides_is_correct_from_serialization(): void
    {
        ['right' => $right] = $this->setupQuiz();

        $this->assertArrayNotHasKey('is_correct', $right->toArray());
    }

    public function test_another_student_cannot_view_someone_elses_results(): void
    {
        ['course' => $c, 'quiz' => $quiz, 'student' => $s] = $this->setupQuiz();
        $other = User::factory()->student()->create();
        $this->enroll($c, $other);

        $attempt = $this->makeAttempt($quiz, $s, [
            'status' => QuizAttempt::STATUS_GRADED,
            'score' => 10,
        ]);

        $this->actingAs($other)
            ->get("/kelas/{$c->id}/kuis/{$quiz->id}/hasil/{$attempt->id}")
            ->assertForbidden();
    }

    public function test_teacher_can_view_student_results(): void
    {
        ['course' => $c, 'quiz' => $quiz, 'student' => $s, 'teacher' => $t] = $this->setupQuiz();

        $attempt = $this->makeAttempt($quiz, $s, [
            'status' => QuizAttempt::STATUS_GRADED,
            'score' => 10,
        ]);

        $this->actingAs($t)
            ->get("/kelas/{$c->id}/kuis/{$quiz->id}/hasil/{$attempt->id}")
            ->assertOk();
    }

    public function test_student_cannot_create_or_edit_quiz(): void
    {
        ['course' => $c, 'quiz' => $quiz, 'student' => $s] = $this->setupQuiz();

        $this->actingAs($s)->get("/kelas/{$c->id}/kuis/buat")->assertForbidden();
        $this->actingAs($s)->get("/kelas/{$c->id}/kuis/{$quiz->id}/ubah")->assertForbidden();
        $this->actingAs($s)
            ->put("/kelas/{$c->id}/kuis/{$quiz->id}", ['title' => 'Diubah murid'])
            ->assertForbidden();
    }
}
