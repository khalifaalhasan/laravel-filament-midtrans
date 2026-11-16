<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;

// Ticket types follow their event: whoever may update the event manages its tickets.
class TicketTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', Event::class);
    }

    public function view(User $user, TicketType $ticketType): bool
    {
        return $user->can('update', $ticketType->event);
    }

    public function create(User $user): bool
    {
        return $user->can('viewAny', Event::class);
    }

    public function update(User $user, TicketType $ticketType): bool
    {
        return $user->can('update', $ticketType->event);
    }

    public function delete(User $user, TicketType $ticketType): bool
    {
        // Sold tickets must not vanish with their type.
        return $ticketType->sold === 0 && $user->can('update', $ticketType->event);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
