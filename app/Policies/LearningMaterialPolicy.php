<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\User;

class LearningMaterialPolicy
{
    use AuthorizesCourseAccess;

    public function viewAny(User $user, Course $course): bool
    {
        return $this->isMember($user, $course);
    }

    public function view(User $user, LearningMaterial $material): bool
    {
        if (! $this->isMember($user, $material->course)) {
            return false;
        }

        // Murid hanya melihat materi yang sudah terbit.
        if ($this->isStudent($user, $material->course)) {
            return $material->isPublished();
        }

        return true;
    }

    public function create(User $user, Course $course): bool
    {
        return $this->isManager($user, $course);
    }

    public function update(User $user, LearningMaterial $material): bool
    {
        return $this->isManager($user, $material->course);
    }

    public function delete(User $user, LearningMaterial $material): bool
    {
        return $this->isManager($user, $material->course);
    }

    /** Menandai materi selesai dilakukan murid anggota kelas. */
    public function markComplete(User $user, LearningMaterial $material): bool
    {
        return $material->isPublished() && $this->isMember($user, $material->course);
    }
}
