<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\DiscussionPost;
use App\Models\DiscussionThread;
use App\Models\User;

class DiscussionPolicy
{
    use AuthorizesCourseAccess;

    public function viewAny(User $user, Course $course): bool
    {
        return $this->isMember($user, $course);
    }

    public function view(User $user, DiscussionThread $thread): bool
    {
        return $this->isMember($user, $thread->course);
    }

    public function createThread(User $user, Course $course): bool
    {
        return $this->isMember($user, $course);
    }

    public function reply(User $user, DiscussionThread $thread): bool
    {
        if (! $this->isMember($user, $thread->course)) {
            return false;
        }

        // Thread terkunci: hanya pengelola yang boleh membalas.
        return ! $thread->is_locked || $this->isManager($user, $thread->course);
    }

    public function deleteThread(User $user, DiscussionThread $thread): bool
    {
        if ($this->isManager($user, $thread->course)) {
            return true;
        }

        return $thread->user_id === $user->id && $thread->posts()->count() <= 1;
    }

    public function moderate(User $user, Course $course): bool
    {
        return $this->isManager($user, $course);
    }

    public function deletePost(User $user, DiscussionPost $post): bool
    {
        if ($this->isManager($user, $post->thread->course)) {
            return true;
        }

        return $post->user_id === $user->id;
    }
}
