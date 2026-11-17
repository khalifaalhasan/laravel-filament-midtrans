<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /** Hold seats and create a pending order. Throws ValidationException when not buyable or sold out. */
    public function reserve(User $user, TicketType $ticketType, int $quantity): Order
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah tiket minimal 1.']);
        }

        if (! $ticketType->isOnSale()) {
            throw ValidationException::withMessages(['ticket_type_id' => 'Tiket ini sedang tidak dijual.']);
        }

        return DB::transaction(function () use ($user, $ticketType, $quantity) {
            // Atomic check-and-increment: works without row locks (SQLite) and cannot oversell under concurrency.
            $reserved = TicketType::whereKey($ticketType->getKey())
                ->whereRaw('sold + ? <= quota', [$quantity])
                ->increment('sold', $quantity);

            if ($reserved === 0) {
                throw ValidationException::withMessages(['quantity' => 'Sisa kuota tidak mencukupi.']);
            }

            return Order::create([
                'code' => 'ORD-'.Str::upper(Str::ulid()),
                'user_id' => $user->getKey(),
                'event_id' => $ticketType->event_id,
                'ticket_type_id' => $ticketType->getKey(),
                'quantity' => $quantity,
                'unit_price' => $ticketType->price,
                'total' => $ticketType->price * $quantity,
                'expires_at' => now()->addMinutes(Order::RESERVATION_MINUTES),
            ]);
        });
    }

    /**
     * Move an order from one of $from to $to exactly once. Returns false if another process already moved it.
     * Releases the held seats when $to gives them back.
     */
    public function transition(Order $order, OrderStatus $to, array $from = [OrderStatus::Pending], array $attributes = []): bool
    {
        return DB::transaction(function () use ($order, $to, $from, $attributes) {
            $moved = Order::whereKey($order->getKey())
                ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, $from))
                ->update(['status' => $to->value, ...$attributes, 'updated_at' => now()]);

            if ($moved === 0) {
                return false;
            }

            if ($to->releasesQuota()) {
                TicketType::whereKey($order->ticket_type_id)->decrement('sold', $order->quantity);
            }

            $order->refresh();

            return true;
        });
    }
}
