<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

/**
 * Helper bersama untuk pengecekan keanggotaan kelas.
 * Semua policy konten akademik merujuk ke sini sehingga aturan
 * konsisten dan diuji di backend, bukan sekadar menyembunyikan tombol.
 */
trait AuthorizesCourseAccess
{
    /** Pengguna adalah anggota aktif kelas (guru maupun murid) atau admin. */
    protected function isMember(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $course->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists() || $course->teacher_id === $user->id;
    }

    /** Pengguna adalah guru yang mengelola kelas ini. */
    protected function isManager(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isTeacher()) {
            return false;
        }

        if ($course->teacher_id === $user->id) {
            return true;
        }

        return $course->memberships()
            ->where('user_id', $user->id)
            ->where('role', 'teacher')
            ->where('status', 'active')
            ->exists();
    }

    /** Murid yang terdaftar aktif pada kelas. */
    protected function isStudent(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        return $course->memberships()
            ->where('user_id', $user->id)
            ->where('role', 'student')
            ->where('status', 'active')
            ->exists();
    }
}
