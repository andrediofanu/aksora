<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;

class AnnouncementPolicy
{
    use AuthorizesCourseAccess;

    public function viewAny(User $user, Course $course): bool
    {
        return $this->isMember($user, $course);
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if (! $this->isMember($user, $announcement->course)) {
            return false;
        }

        if ($this->isStudent($user, $announcement->course)) {
            return $announcement->isPublished();
        }

        return true;
    }

    public function create(User $user, Course $course): bool
    {
        return $this->isManager($user, $course);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $this->isManager($user, $announcement->course);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->isManager($user, $announcement->course);
    }
}
