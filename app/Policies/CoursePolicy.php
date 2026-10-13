<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($course->teacher_id === $user->id) {
            return true;
        }

        return $course->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->isTeacher();
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher() && $this->ownsCourse($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher()
            && $this->ownsCourse($user, $course)
            && $course->studentCount() === 0;
    }

    public function archive(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    /** Kelola anggota: tambah, keluarkan, undang. */
    public function manageMembers(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    /** Kelola materi/tugas/kuis/pengumuman/diskusi/bobot nilai. */
    public function manageContent(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher() && $this->ownsCourse($user, $course);
    }

    /** Akses gradebook kelas (lihat semua nilai). */
    public function viewGradebook(User $user, Course $course): bool
    {
        return $this->manageContent($user, $course);
    }

    /** Keluar dari kelas (hanya murid anggota). */
    public function leave(User $user, Course $course): bool
    {
        return $course->memberships()
            ->where('user_id', $user->id)
            ->where('role', 'student')
            ->where('status', 'active')
            ->exists();
    }

    private function ownsCourse(User $user, Course $course): bool
    {
        if ($course->teacher_id === $user->id) {
            return true;
        }

        return $course->memberships()
            ->where('user_id', $user->id)
            ->where('role', 'teacher')
            ->where('status', 'active')
            ->exists();
    }
}
