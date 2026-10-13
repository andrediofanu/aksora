<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * CRUD mata pelajaran & tahun ajaran hanya untuk admin,
 * termasuk tampilan tombol hapus (policy) dan pengaktifan tahun ajaran.
 */
class AdminSubjectsAndYearsTest extends TestCase
{
    use BuildsCourses, RefreshDatabase;

    public function test_admin_can_create_subject(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/mata-pelajaran', [
                'name' => 'Fisika',
                'code' => 'FIS',
                'description' => 'Ilmu tentang gaya dan gerak.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', ['name' => 'Fisika', 'code' => 'FIS']);
    }

    public function test_admin_can_update_subject(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::create(['name' => 'Kimia', 'code' => 'KIM']);

        $this->actingAs($admin)
            ->put("/admin/mata-pelajaran/{$subject->id}", [
                'name' => 'Kimia Umum',
                'code' => 'KIM',
                'is_active' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'name' => 'Kimia Umum']);
    }

    public function test_admin_can_delete_subject_and_sees_the_button(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::create(['name' => 'Biologi', 'code' => 'BIO']);

        // Tombol Hapus tampil untuk admin (regresi: policy tidak terdaftar).
        $this->actingAs($admin)
            ->get('/admin/mata-pelajaran')
            ->assertOk()
            ->assertSee('Biologi')
            ->assertSee('Hapus');

        $this->actingAs($admin)
            ->delete("/admin/mata-pelajaran/{$subject->id}")
            ->assertSessionHas('success');

        $this->assertSoftDeleted('subjects', ['id' => $subject->id]);
    }

    public function test_subject_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        ['course' => $course] = $this->makeCourse();
        $subject = $course->subject;

        $this->actingAs($admin)
            ->delete("/admin/mata-pelajaran/{$subject->id}")
            ->assertSessionHasErrors('subject');

        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_teacher_and_student_cannot_manage_subjects(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        foreach ([$teacher, $student] as $user) {
            $this->actingAs($user)->get('/admin/mata-pelajaran')->assertForbidden();
            $this->actingAs($user)
                ->post('/admin/mata-pelajaran', ['name' => 'SastrA', 'code' => 'SA'])
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('subjects', ['name' => 'SastrA']);
    }

    public function test_admin_can_create_active_academic_year_and_deactivate_the_previous_one(): void
    {
        $admin = User::factory()->admin()->create();
        $previous = $this->activeYear();

        $this->actingAs($admin)
            ->post('/admin/tahun-ajaran', [
                'name' => '2027/2028',
                'starts_on' => now()->addYear()->startOfYear()->toDateString(),
                'ends_on' => now()->addYear()->endOfYear()->toDateString(),
                'is_active' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertTrue(AcademicYear::where('name', '2027/2028')->first()->is_active);
        $this->assertFalse($previous->fresh()->is_active);
        $this->assertSame(1, AcademicYear::where('is_active', true)->count());
    }

    public function test_admin_can_activate_and_delete_academic_year(): void
    {
        $admin = User::factory()->admin()->create();
        $year = AcademicYear::create([
            'name' => '2030/2031',
            'starts_on' => '2030-07-01',
            'ends_on' => '2031-06-30',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get('/admin/tahun-ajaran')
            ->assertOk()
            ->assertSee('Hapus');

        $this->actingAs($admin)
            ->post("/admin/tahun-ajaran/{$year->id}/aktifkan")
            ->assertSessionHas('success');
        $this->assertTrue($year->fresh()->is_active);

        $this->actingAs($admin)
            ->delete("/admin/tahun-ajaran/{$year->id}")
            ->assertSessionHas('success');
        $this->assertSoftDeleted('academic_years', ['id' => $year->id]);
    }

    public function test_academic_year_with_courses_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        ['course' => $course] = $this->makeCourse();

        $this->actingAs($admin)
            ->delete("/admin/tahun-ajaran/{$course->academic_year_id}")
            ->assertSessionHasErrors('year');
    }

    public function test_admin_can_reuse_subject_name_and_code_after_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::create(['name' => 'Fisika', 'code' => 'FIS']);

        $this->actingAs($admin)
            ->delete("/admin/mata-pelajaran/{$subject->id}")
            ->assertSessionHas('success');

        // Nama & kode lama harus bisa dipakai lagi setelah baris dihapus (soft delete).
        $this->actingAs($admin)
            ->post('/admin/mata-pelajaran', [
                'name' => 'Fisika',
                'code' => 'FIS',
                'description' => 'Dibuat ulang.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', ['name' => 'Fisika', 'code' => 'FIS', 'deleted_at' => null]);
    }

    public function test_admin_can_reuse_academic_year_name_after_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $year = AcademicYear::create([
            'name' => '2040/2041',
            'starts_on' => '2040-07-01',
            'ends_on' => '2041-06-30',
        ]);

        $this->actingAs($admin)
            ->delete("/admin/tahun-ajaran/{$year->id}")
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post('/admin/tahun-ajaran', [
                'name' => '2040/2041',
                'starts_on' => '2040-07-01',
                'ends_on' => '2041-06-30',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('academic_years', ['name' => '2040/2041', 'deleted_at' => null]);
    }

    public function test_deleting_active_year_activates_the_next_newest_year(): void
    {
        $admin = User::factory()->admin()->create();
        $active = $this->activeYear();
        $older = AcademicYear::create([
            'name' => '2025/2026',
            'starts_on' => now()->subYear()->toDateString(),
            'ends_on' => now()->subMonths(1)->toDateString(),
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/tahun-ajaran/{$active->id}")
            ->assertSessionHas('success');

        $this->assertSoftDeleted('academic_years', ['id' => $active->id]);
        $this->assertTrue($older->fresh()->is_active, 'Tahun lain harus otomatis aktif.');
        $this->assertSame(1, AcademicYear::where('is_active', true)->count());
    }

    public function test_teacher_and_student_cannot_manage_academic_years(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        foreach ([$teacher, $student] as $user) {
            $this->actingAs($user)->get('/admin/tahun-ajaran')->assertForbidden();
            $this->actingAs($user)
                ->post('/admin/tahun-ajaran', [
                    'name' => 'X/Y',
                    'starts_on' => '2031-07-01',
                    'ends_on' => '2032-06-30',
                ])
                ->assertForbidden();
        }
    }
}
