<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;

class AssignmentPolicy
{
    use AuthorizesCourseAccess;

    public function viewAny(User $user, Course $course): bool
    {
        return $this->isMember($user, $course);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if (! $this->isMember($user, $assignment->course)) {
            return false;
        }

        if ($this->isStudent($user, $assignment->course)) {
            return $assignment->isPublished();
        }

        return true;
    }

    public function create(User $user, Course $course): bool
    {
        return $this->isManager($user, $course);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $this->isManager($user, $assignment->course);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->isManager($user, $assignment->course);
    }

    /** Murid mengumpulkan tugas. */
    public function submit(User $user, Assignment $assignment): bool
    {
        if (! $this->isStudent($user, $assignment->course)) {
            return false;
        }

        if (! $assignment->isPublished()) {
            return false;
        }

        if ($assignment->due_at !== null
            && $assignment->due_at->isPast()
            && ! $assignment->allow_late) {
            return false;
        }

        return true;
    }

    /** Guru/admin melihat seluruh pengumpulan kelas. */
    public function viewSubmissions(User $user, Assignment $assignment): bool
    {
        return $this->isManager($user, $assignment->course);
    }

    public function grade(User $user, Assignment $assignment): bool
    {
        return $this->isManager($user, $assignment->course);
    }
}
