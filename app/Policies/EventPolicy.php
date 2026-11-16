<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

// PO rights are always checked against the specific event, never by role name alone.
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->managedEvents()->exists();
    }

    public function view(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isStaff() || $user->isProjectOfficerOf($event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasRole('ceo');
    }

    public function assignOfficers(User $user, Event $event): bool
    {
        return $user->hasRole('ceo');
    }
}
