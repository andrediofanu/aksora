<?php

namespace Tests\Concerns;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseMembership;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;

/**
 * Pembuat data uji berulang untuk kelas, anggota, dan konten akademik.
 * Semua data bersifat fiktif dan hanya dipakai di lingkungan pengujian.
 */
trait BuildsCourses
{
    /** Tahun ajaran aktif (dibuat sekali per test). */
    protected function activeYear(): AcademicYear
    {
        return AcademicYear::firstOrCreate(
            ['name' => now()->year.'/'.(now()->year + 1)],
            [
                'starts_on' => now()->subMonths(3)->startOfMonth()->toDateString(),
                'ends_on' => now()->addMonths(9)->endOfMonth()->toDateString(),
                'is_active' => true,
                'description' => 'Tahun ajaran uji otomatis.',
            ]
        );
    }

    /**
     * Buat kelas lengkap dengan guru pengampu + tahun ajaran.
     *
     * @return array{course: Course, teacher: User}
     */
    protected function makeCourse(?User $teacher = null, array $attributes = []): array
    {
        $teacher ??= User::factory()->teacher()->create();

        $course = Course::create(array_merge([
            'name' => 'Matematika Kelas X IPA 1',
            'code' => strtoupper(substr(md5((string) microtime(true).mt_rand()), 0, 7)),
            'description' => 'Kelas uji otomatis.',
            'subject_id' => Subject::firstOrCreate(['name' => 'Matematika'], ['code' => 'MTK1'])->id,
            'academic_year_id' => $this->activeYear()->id,
            'teacher_id' => $teacher->id,
            'grade_level' => 'Kelas X',
            'room' => 'R-1',
            'status' => Course::STATUS_ACTIVE,
            'max_members' => 40,
            'allow_student_private_message' => true,
        ], $attributes));

        return ['course' => $course, 'teacher' => $teacher];
    }

    /** Daftarkan murid sebagai anggota aktif kelas. */
    protected function enroll(Course $course, User $student, string $role = 'student'): CourseMembership
    {
        return CourseMembership::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $student->id],
            [
                'role' => $role,
                'status' => 'active',
                'joined_source' => 'code',
                'joined_at' => now(),
                'left_at' => null,
            ]
        );
    }

    /** Materi terbit pada kelas. */
    protected function makeMaterial(Course $course, User $creator, array $attributes = []): LearningMaterial
    {
        return LearningMaterial::create(array_merge([
            'course_id' => $course->id,
            'title' => 'Pengenalan Bilangan Real',
            'topic' => 'Pertemuan 1',
            'description' => 'Materi uji.',
            'content' => '<p>Isi materi uji.</p>',
            'sort_order' => 1,
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'created_by' => $creator->id,
        ], $attributes));
    }

    /** Tugas terbit pada kelas. */
    protected function makeAssignment(Course $course, User $creator, array $attributes = []): Assignment
    {
        return Assignment::create(array_merge([
            'course_id' => $course->id,
            'title' => 'Tugas Bab 1',
            'instructions' => 'Kerjakan soal halaman 10.',
            'submission_type' => 'text',
            'due_at' => now()->addDays(7),
            'allow_late' => false,
            'max_score' => 100,
            'weight' => 1,
            'status' => Assignment::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'created_by' => $creator->id,
        ], $attributes));
    }

    /** Kuis terbit tanpa soal (untuk uji otorisasi). */
    protected function makeQuiz(Course $course, User $creator, array $attributes = []): Quiz
    {
        return Quiz::create(array_merge([
            'course_id' => $course->id,
            'title' => 'Kuis Bab 1',
            'description' => 'Kuis uji.',
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDays(7),
            'max_attempts' => 3,
            'show_results' => 'immediately',
            'pass_score' => 70,
            'status' => Quiz::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'created_by' => $creator->id,
        ], $attributes));
    }

    /** Pengumpulan tugas milik murid tertentu. */
    protected function makeSubmission(Assignment $assignment, User $student, array $attributes = []): AssignmentSubmission
    {
        return AssignmentSubmission::create(array_merge([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'Jawaban murid uji.',
            'submitted_at' => now(),
            'is_late' => false,
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ], $attributes));
    }

    /** Percobaan kuis milik murid. */
    protected function makeAttempt(Quiz $quiz, User $student, array $attributes = []): QuizAttempt
    {
        return QuizAttempt::create(array_merge([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'attempt_no' => 1,
            'started_at' => now(),
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
        ], $attributes));
    }
}
