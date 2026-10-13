<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\User;
use App\Services\GradeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * Privasi nilai: murid hanya melihat nilai miliknya sendiri,
 * guru/admin melihat buku nilai kelasnya.
 */
class GradePrivacyTest extends TestCase
{
    use BuildsCourses, RefreshDatabase;

    public function test_student_cannot_open_course_gradebook(): void
    {
        ['course' => $course] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $this->actingAs($student)->get("/kelas/{$course->id}/nilai")->assertForbidden();
        $this->actingAs($student)->get("/kelas/{$course->id}/nilai/ekspor")->assertForbidden();
    }

    public function test_gradebook_post_weights_forbidden_for_student(): void
    {
        ['course' => $course] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $this->actingAs($student)
            ->post("/kelas/{$course->id}/nilai/bobot", [
                'weights' => ['assignment' => 100, 'quiz' => 0],
            ])
            ->assertForbidden();
    }

    public function test_course_teacher_can_open_gradebook_and_export_csv(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();

        $this->actingAs($teacher)->get("/kelas/{$course->id}/nilai")->assertOk();

        $export = $this->actingAs($teacher)->get("/kelas/{$course->id}/nilai/ekspor");
        $export->assertOk();
        $this->assertStringContainsString('text/csv', $export->headers->get('Content-Type'));
    }

    public function test_other_teacher_cannot_open_gradebook(): void
    {
        ['course' => $course] = $this->makeCourse();
        $other = User::factory()->teacher()->create();

        $this->actingAs($other)->get("/kelas/{$course->id}/nilai")->assertForbidden();
    }

    public function test_my_grades_page_shows_only_own_name_not_classmates(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $me = User::factory()->student()->create();
        $classmate = User::factory()->student()->create();
        $this->enroll($course, $me);
        $this->enroll($course, $classmate);

        $assignment = $this->makeAssignment($course, $teacher);
        $this->makeSubmission($assignment, $classmate, ['score' => 99, 'status' => 'graded']);

        $response = $this->actingAs($me)->get('/nilai-saya');

        $response->assertOk();
        $response->assertDontSee($classmate->name);
        // Nilai kelas lain (99) tidak boleh muncul di halaman nilai saya.
        $response->assertDontSee('99.0');
    }

    public function test_my_grades_page_shows_own_final_grade(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $assignment = $this->makeAssignment($course, $teacher, ['max_score' => 100, 'weight' => 1]);
        $this->makeSubmission($assignment, $student, ['score' => 80, 'status' => 'graded']);

        $this->actingAs($student)
            ->get('/nilai-saya')
            ->assertOk()
            ->assertSee($course->name);
    }

    public function test_gradebook_rows_contain_enrolled_students_only(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $member = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();
        $this->enroll($course, $member);

        $calculator = app(GradeCalculator::class);
        $rows = $calculator->gradebook($course);

        $ids = $rows->pluck('student.id')->all();
        $this->assertContains($member->id, $ids);
        $this->assertNotContains($outsider->id, $ids);
    }

    public function test_weighted_final_grade_formula_uses_documented_weights(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        // Tugas: 80/100 → 0.8 ; Kuis tidak ada → hanya kategori tugas yang dinilai.
        $assignment = $this->makeAssignment($course, $teacher, ['max_score' => 100, 'weight' => 1]);
        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'jawaban',
            'submitted_at' => now(),
            'status' => AssignmentSubmission::STATUS_GRADED,
            'score' => 80,
            'graded_at' => now(),
            'graded_by' => $teacher->id,
        ]);

        $grade = app(GradeCalculator::class)->forStudent($course, $student);

        // Kategori kuis kosong → dikecualikan, bobot dinormalkan ulang → 80.
        $this->assertSame(80.0, (float) $grade['final']);
    }

    public function test_ungraded_student_gets_null_final_grade(): void
    {
        ['course' => $course] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $grade = app(GradeCalculator::class)->forStudent($course, $student);

        $this->assertNull($grade['final']);
    }
}
