<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    use AuthorizesCourseAccess;

    /**
     * Murid hanya boleh melihat percobaan miliknya sendiri.
     * Guru/admin boleh melihat percobaan di kelas yang mereka kelola.
     */
    public function view(User $user, QuizAttempt $attempt): bool
    {
        if ($attempt->user_id === $user->id) {
            return true;
        }

        return $this->isManager($user, $attempt->quiz->course);
    }

    /** Menjawab/mengirim hanya oleh pemilik percobaan yang masih berjalan. */
    public function answer(User $user, QuizAttempt $attempt): bool
    {
        if ($attempt->user_id !== $user->id) {
            return false;
        }

        if (! $this->isStudent($user, $attempt->quiz->course)) {
            return false;
        }

        return $attempt->isInProgress();
    }
}
