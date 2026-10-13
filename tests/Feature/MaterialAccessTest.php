<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * Akses materi: murid hanya melihat materi terbit di kelasnya sendiri.
 */
class MaterialAccessTest extends TestCase
{
    use BuildsCourses, RefreshDatabase;

    public function test_member_student_can_view_published_material(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $material = $this->makeMaterial($course, $teacher);

        $this->actingAs($student)
            ->get("/kelas/{$course->id}/materi/{$material->id}")
            ->assertOk()
            ->assertSee($material->title);
    }

    public function test_member_student_cannot_view_draft_material(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $draft = $this->makeMaterial($course, $teacher, [
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->actingAs($student)
            ->get("/kelas/{$course->id}/materi/{$draft->id}")
            ->assertForbidden();
    }

    public function test_draft_material_is_hidden_from_student_listing(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $published = $this->makeMaterial($course, $teacher, ['title' => 'Materi Terbit']);
        $draft = $this->makeMaterial($course, $teacher, [
            'title' => 'Materi Draf Rahasia',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->actingAs($student)
            ->get("/kelas/{$course->id}/materi")
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);
    }

    public function test_teacher_sees_draft_material_in_listing(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $draft = $this->makeMaterial($course, $teacher, [
            'title' => 'Materi Draf Guru',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->actingAs($teacher)
            ->get("/kelas/{$course->id}/materi")
            ->assertOk()
            ->assertSee($draft->title);
    }

    public function test_outsider_student_cannot_view_material(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $material = $this->makeMaterial($course, $teacher);
        $outsider = User::factory()->student()->create();

        $this->actingAs($outsider)
            ->get("/kelas/{$course->id}/materi/{$material->id}")
            ->assertForbidden();
    }

    public function test_student_cannot_create_material(): void
    {
        ['course' => $course] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $this->actingAs($student)
            ->post("/kelas/{$course->id}/materi", [
                'title' => 'Materi Ilegal',
                'topic' => 'Pertemuan 1',
                'status' => 'published',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('learning_materials', ['title' => 'Materi Ilegal']);
    }

    public function test_student_can_mark_material_complete_only_once_row(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);
        $material = $this->makeMaterial($course, $teacher);

        $this->actingAs($student)
            ->post("/kelas/{$course->id}/materi/{$material->id}/selesai")
            ->assertRedirect();

        $this->assertSame(
            1,
            \App\Models\MaterialCompletion::where('learning_material_id', $material->id)
                ->where('user_id', $student->id)
                ->count()
        );
    }
}
