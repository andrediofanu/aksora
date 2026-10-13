<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * Keanggotaan kelas: gabung via kode/undangan, keluar, dan
 * penjaminan tanpa duplikasi (unique constraint course_id + user_id).
 */
class CourseMembershipTest extends TestCase
{
    use BuildsCourses, RefreshDatabase;

    public function test_student_joins_course_with_valid_code(): void
    {
        ['course' => $course] = $this->makeCourse();
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post('/kelas/gabung', ['code' => $course->code])
            ->assertRedirect("/kelas/{$course->id}");

        $this->assertDatabaseHas('course_memberships', [
            'course_id' => $course->id,
            'user_id' => $student->id,
            'role' => 'student',
            'status' => 'active',
        ]);
    }

    public function test_joining_twice_does_not_duplicate_membership(): void
    {
        ['course' => $course] = $this->makeCourse();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->post('/kelas/gabung', ['code' => $course->code]);
        $this->actingAs($student)->post('/kelas/gabung', ['code' => $course->code]);

        $this->assertSame(
            1,
            CourseMembership::where('course_id', $course->id)->where('user_id', $student->id)->count()
        );
    }

    public function test_join_with_unknown_code_fails_validation(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->from('/kelas/gabung')
            ->post('/kelas/gabung', ['code' => 'TIDAKADA'])
            ->assertRedirect('/kelas/gabung')
            ->assertSessionHasErrors('code');
    }

    public function test_teacher_cannot_join_a_course_as_student(): void
    {
        ['course' => $course] = $this->makeCourse();
        $otherTeacher = User::factory()->teacher()->create();

        $this->actingAs($otherTeacher)
            ->from('/kelas/gabung')
            ->post('/kelas/gabung', ['code' => $course->code])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('course_memberships', [
            'course_id' => $course->id,
            'user_id' => $otherTeacher->id,
        ]);
    }

    public function test_archived_course_cannot_be_joined(): void
    {
        ['course' => $course] = $this->makeCourse(null, [
            'status' => Course::STATUS_ARCHIVED,
        ]);
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->from('/kelas/gabung')
            ->post('/kelas/gabung', ['code' => $course->code])
            ->assertSessionHasErrors('code');
    }

    public function test_joined_student_gets_membership(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $this->actingAs($teacher)
            ->get("/kelas/{$course->id}/anggota")
            ->assertOk()
            ->assertSee($student->name);
    }

    public function test_student_can_leave_course_and_membership_is_removed(): void
    {
        ['course' => $course] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $this->actingAs($student)
            ->post("/kelas/{$course->id}/keluar")
            ->assertRedirect('/kelas');

        $membership = CourseMembership::where('course_id', $course->id)
            ->where('user_id', $student->id)
            ->firstOrFail();

        $this->assertSame('removed', $membership->status);
        $this->assertNotNull($membership->left_at);

        // Setelah keluar, akses kelas ditutup.
        $this->actingAs($student)->get("/kelas/{$course->id}")->assertForbidden();
    }

    public function test_teacher_cannot_leave_student_membership_route(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();

        // Hanya murid anggota yang boleh keluar; policy `leave` menolak guru.
        $this->actingAs($teacher)->post("/kelas/{$course->id}/keluar")->assertForbidden();
    }

    public function test_non_owner_teacher_cannot_remove_members(): void
    {
        ['course' => $course] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);
        $otherTeacher = User::factory()->teacher()->create();

        $this->actingAs($otherTeacher)
            ->delete("/kelas/{$course->id}/anggota/{$student->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('course_memberships', [
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
        ]);
    }

    public function test_owner_teacher_can_remove_member(): void
    {
        ['course' => $course, 'teacher' => $teacher] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $this->actingAs($teacher)
            ->delete("/kelas/{$course->id}/anggota/{$student->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('course_memberships', [
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'removed',
        ]);
    }

    public function test_unique_constraint_prevents_duplicate_membership_rows(): void
    {
        ['course' => $course] = $this->makeCourse();
        $student = User::factory()->student()->create();
        $this->enroll($course, $student);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CourseMembership::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'role' => 'student',
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }
}
