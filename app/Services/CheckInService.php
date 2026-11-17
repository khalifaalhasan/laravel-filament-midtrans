<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Ticket;
use App\Models\User;
use RuntimeException;

class CheckInService
{
    /** Mark a ticket as checked in. Throws RuntimeException with a user-facing message when it must be refused. */
    public function checkIn(string $code, User $by): Ticket
    {
        $ticket = Ticket::with('order', 'event')->firstWhere('code', strtoupper(trim($code)));

        // Same message for unknown and other-event tickets so a PO cannot probe other events' codes.
        if ($ticket === null || ! ($by->isStaff() || $by->isProjectOfficerOf($ticket->event))) {
            throw new RuntimeException('Tiket tidak ditemukan untuk event yang Anda kelola.');
        }

        if ($ticket->order->status !== OrderStatus::Paid) {
            throw new RuntimeException('Tiket tidak berlaku: pesanan '.strtolower($ticket->order->status->label()).'.');
        }

        // Atomic: two scanners on the same code cannot both succeed.
        $updated = Ticket::whereKey($ticket->getKey())
            ->whereNull('checked_in_at')
            ->update(['checked_in_at' => now(), 'checked_in_by' => $by->getKey(), 'updated_at' => now()]);

        if ($updated === 0) {
            throw new RuntimeException('Tiket sudah check-in pada '.$ticket->refresh()->checked_in_at->translatedFormat('d M Y, H:i').'.');
        }

        return $ticket->refresh();
    }
}
