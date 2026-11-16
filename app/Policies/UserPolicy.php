<?php

namespace App\Policies;

use App\Models\User;

// Only the CEO manages accounts, roles, and PO assignments.
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ceo');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('ceo');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ceo');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('ceo');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('ceo') && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('ceo');
    }
}
