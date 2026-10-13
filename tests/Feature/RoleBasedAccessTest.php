<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * Hak akses berbasis peran (RBAC). Pemeriksaan dilakukan di backend,
 * bukan sekadar menyembunyikan tombol di Blade.
 */
class RoleBasedAccessTest extends TestCase
{
    use BuildsCourses, RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/kelas')->assertRedirect('/login');
        $this->get('/percakapan')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_student_cannot_open_admin_panel(): void
    {
        $student = User::factory()->student()->create();

        foreach (['/admin', '/admin/pengguna', '/admin/pengaturan', '/admin/log-aktivitas'] as $uri) {
            $this->actingAs($student)->get($uri)->assertForbidden();
        }
    }

    public function test_teacher_cannot_open_admin_panel(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/admin')->assertForbidden();
        $this->actingAs($teacher)->get('/admin/pengguna')->assertForbidden();
    }

    public function test_admin_can_open_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/pengguna')->assertOk();
        $this->actingAs($admin)->get('/admin/pengaturan')->assertOk();
    }

    public function test_student_cannot_create_course(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/kelas/buat')->assertForbidden();
    }

    public function test_teacher_can_create_course(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/kelas/buat')->assertOk();
    }

    public function test_teacher_cannot_manage_content_of_someone_elses_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();

        ['course' => $course] = $this->makeCourse($owner);

        $this->actingAs($other)->get("/kelas/{$course->id}/materi/buat")->assertForbidden();
        $this->actingAs($other)->get("/kelas/{$course->id}/tugas/buat")->assertForbidden();
        $this->actingAs($other)->get("/kelas/{$course->id}/nilai")->assertForbidden();
        $this->actingAs($other)->delete("/kelas/{$course->id}")->assertForbidden();
    }

    public function test_owner_teacher_can_open_content_forms(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();

        $this->actingAs($teacher)->get("/kelas/{$course->id}/materi/buat")->assertOk();
        $this->actingAs($teacher)->get("/kelas/{$course->id}/tugas/buat")->assertOk();
        $this->actingAs($teacher)->get("/kelas/{$course->id}/nilai")->assertOk();
    }

    public function test_non_member_student_cannot_open_course(): void
    {
        ['course' => $course] = $this->makeCourse();
        $outsider = User::factory()->student()->create();

        $this->actingAs($outsider)->get("/kelas/{$course->id}")->assertForbidden();
        $this->actingAs($outsider)->get("/kelas/{$course->id}/materi")->assertForbidden();
        $this->actingAs($outsider)->get("/kelas/{$course->id}/tugas")->assertForbidden();
    }

    public function test_inactive_account_is_logged_out(): void
    {
        $user = User::factory()->student()->inactive()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
