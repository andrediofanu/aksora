<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * Pengumpulan & penilaian tugas.
 */
class AssignmentSubmissionTest extends TestCase
{
    use BuildsCourses, RefreshDatabase;

    private function setupCourse(): array
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        return compact('course', 'teacher', 'student');
    }

    public function test_student_can_submit_text_assignment(): void
    {
        ['course' => $course, 'teacher' => $teacher, 'student' => $student] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher);

        $this->actingAs($student)
            ->post("/kelas/{$course->id}/tugas/{$assignment->id}/kumpul", [
                'content' => 'Ini jawaban saya.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'Ini jawaban saya.',
            'status' => 'submitted',
        ]);
    }

    public function test_resubmission_updates_existing_row_and_clears_old_score(): void
    {
        ['course' => $course, 'teacher' => $teacher, 'student' => $student] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher);
        $this->makeSubmission($assignment, $student, ['score' => 80, 'status' => 'graded']);

        $this->actingAs($student)
            ->post("/kelas/{$course->id}/tugas/{$assignment->id}/kumpul", [
                'content' => 'Jawaban revisi.',
            ])
            ->assertRedirect();

        $this->assertSame(
            1,
            \App\Models\AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('user_id', $student->id)
                ->count()
        );

        $row = \App\Models\AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->first();

        $this->assertSame('Jawaban revisi.', $row->content);
        $this->assertNull($row->score, 'Nilai lama harus direset saat pengumpulan ulang.');
    }

    public function test_non_member_student_cannot_submit(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher);
        $outsider = User::factory()->student()->create();

        $this->actingAs($outsider)
            ->post("/kelas/{$course->id}/tugas/{$assignment->id}/kumpul", [
                'content' => 'Nyasar.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('assignment_submissions', ['content' => 'Nyasar.']);
    }

    public function test_teacher_cannot_submit_as_student(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher);

        $this->actingAs($teacher)
            ->post("/kelas/{$course->id}/tugas/{$assignment->id}/kumpul", [
                'content' => 'Guru mengumpul.',
            ])
            ->assertForbidden();
    }

    public function test_student_cannot_submit_after_deadline_when_late_is_disallowed(): void
    {
        ['course' => $course, 'teacher' => $teacher, 'student' => $student] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher, [
            'due_at' => now()->subDay(),
            'allow_late' => false,
        ]);

        // Policy `submit` menolak pengumpulan setelah tenggat bila izin telat dimatikan.
        $this->actingAs($student)
            ->post("/kelas/{$course->id}/tugas/{$assignment->id}/kumpul", [
                'content' => 'Terlambat.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('assignment_submissions', ['content' => 'Terlambat.']);
    }

    public function test_only_course_teacher_can_view_submissions_list(): void
    {
        ['course' => $course, 'teacher' => $teacher, 'student' => $student] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher);
        $this->makeSubmission($assignment, $student);

        $this->actingAs($teacher)
            ->get("/kelas/{$course->id}/tugas/{$assignment->id}/pengumpulan")
            ->assertOk();

        // Murid tidak boleh melihat daftar pengumpulan (menyimpan data murid lain).
        $this->actingAs($student)
            ->get("/kelas/{$course->id}/tugas/{$assignment->id}/pengumpulan")
            ->assertForbidden();
    }

    public function test_teacher_can_grade_submission_and_student_gets_notification(): void
    {
        ['course' => $course, 'teacher' => $teacher, 'student' => $student] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher);
        $submission = $this->makeSubmission($assignment, $student);

        $this->actingAs($teacher)
            ->post("/kelas/{$course->id}/tugas/{$assignment->id}/pengumpulan/{$submission->id}/nilai", [
                'score' => 90,
                'feedback' => 'Bagus, pertahankan.',
                'status' => 'graded',
            ])
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame(90.0, (float) $submission->score);
        $this->assertSame('Bagus, pertahankan.', $submission->feedback);
        $this->assertSame($teacher->id, $submission->graded_by);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->id,
            'type' => \Illuminate\Notifications\DatabaseNotification::class,
        ]);
    }

    public function test_student_cannot_grade_any_submission(): void
    {
        ['course' => $course, 'teacher' => $teacher, 'student' => $student] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher);
        $own = $this->makeSubmission($assignment, $student);

        $this->actingAs($student)
            ->post("/kelas/{$course->id}/tugas/{$assignment->id}/pengumpulan/{$own->id}/nilai", [
                'score' => 100,
            ])
            ->assertForbidden();

        $this->assertNull($own->fresh()->score);
    }

    public function test_grade_score_is_validated_against_max_score(): void
    {
        ['course' => $course, 'teacher' => $teacher, 'student' => $student] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher, ['max_score' => 100]);
        $submission = $this->makeSubmission($assignment, $student);

        $this->actingAs($teacher)
            ->post("/kelas/{$course->id}/tugas/{$assignment->id}/pengumpulan/{$submission->id}/nilai", [
                'score' => 500,
            ])
            ->assertSessionHasErrors('score');
    }

    public function test_student_can_delete_own_ungraded_submission(): void
    {
        ['course' => $course, 'teacher' => $teacher, 'student' => $student] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher);
        $submission = $this->makeSubmission($assignment, $student);

        $this->actingAs($student)
            ->delete("/kelas/{$course->id}/tugas/{$assignment->id}/pengumpulan/{$submission->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('assignment_submissions', ['id' => $submission->id]);
    }

    public function test_student_cannot_delete_a_graded_submission(): void
    {
        ['course' => $course, 'teacher' => $teacher, 'student' => $student] = $this->setupCourse();
        $assignment = $this->makeAssignment($course, $teacher);
        $submission = $this->makeSubmission($assignment, $student, ['score' => 75, 'status' => 'graded']);

        $this->actingAs($student)
            ->delete("/kelas/{$course->id}/tugas/{$assignment->id}/pengumpulan/{$submission->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('assignment_submissions', ['id' => $submission->id]);
    }
}
