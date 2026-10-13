<?php

namespace App\Policies;

use App\Models\AssignmentSubmission;
use App\Models\User;

class AssignmentSubmissionPolicy
{
    use AuthorizesCourseAccess;

    public function view(User $user, AssignmentSubmission $submission): bool
    {
        // Pemilik pengumpulan hanya boleh melihat miliknya sendiri.
        if ($submission->user_id === $user->id) {
            return true;
        }

        return $this->isManager($user, $submission->assignment->course);
    }

    public function update(User $user, AssignmentSubmission $submission): bool
    {
        return $this->isManager($user, $submission->assignment->course);
    }

    public function delete(User $user, AssignmentSubmission $submission): bool
    {
        if ($submission->user_id === $user->id) {
            return $this->isStudent($user, $submission->assignment->course);
        }

        return $this->isManager($user, $submission->assignment->course);
    }
}
