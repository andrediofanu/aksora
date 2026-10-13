<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->isAdmin() || $user->isTeacher();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        // Admin tidak boleh menonaktifkan akunnya sendiri.
        return $user->id !== $model->id;
    }

    public function changeRole(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }
}
