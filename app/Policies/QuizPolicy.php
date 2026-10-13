<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

class QuizPolicy
{
    use AuthorizesCourseAccess;

    public function viewAny(User $user, Course $course): bool
    {
        return $this->isMember($user, $course);
    }

    public function view(User $user, Quiz $quiz): bool
    {
        if (! $this->isMember($user, $quiz->course)) {
            return false;
        }

        if ($this->isStudent($user, $quiz->course)) {
            return $quiz->isPublished();
        }

        return true;
    }

    public function create(User $user, Course $course): bool
    {
        return $this->isManager($user, $course);
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->isManager($user, $quiz->course);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->isManager($user, $quiz->course);
    }

    public function start(User $user, Quiz $quiz): bool
    {
        if (! $this->isStudent($user, $quiz->course)) {
            return false;
        }

        return $quiz->canAttempt($user);
    }

    public function viewResults(User $user, Quiz $quiz): bool
    {
        if ($this->isManager($user, $quiz->course)) {
            return true;
        }

        return $this->isStudent($user, $quiz->course) && $quiz->show_results !== 'never';
    }
}
