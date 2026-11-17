<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Payments\PaymentUpdate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * Hold seats for several ticket types of one event and create a pending order.
     *
     * @param  array<int, int>  $quantities  ticket_type_id => quantity
     *
     * @throws ValidationException when a type is not buyable, the total exceeds the limit, or seats ran out.
     *                             Nothing stays reserved when any line fails.
     */
    public function reserve(User $user, array $quantities): Order
    {
        $quantities = array_filter(array_map('intval', $quantities), fn (int $qty) => $qty > 0);

        if ($quantities === []) {
            throw ValidationException::withMessages(['quantities' => 'Pilih minimal 1 tiket.']);
        }

        if (array_sum($quantities) > Order::MAX_TICKETS) {
            throw ValidationException::withMessages(['quantities' => 'Maksimal '.Order::MAX_TICKETS.' tiket dalam 1 transaksi.']);
        }

        $types = TicketType::with('event')->findMany(array_keys($quantities))->keyBy('id');

        if ($types->count() !== count($quantities) || $types->pluck('event_id')->unique()->count() !== 1) {
            throw ValidationException::withMessages(['quantities' => 'Jenis tiket tidak valid.']);
        }

        foreach ($types as $type) {
            if (! $type->isOnSale()) {
                throw ValidationException::withMessages(['quantities' => "Tiket {$type->name} sedang tidak dijual."]);
            }
        }

        return DB::transaction(function () use ($user, $quantities, $types) {
            foreach ($quantities as $typeId => $quantity) {
                // Atomic check-and-increment: works without row locks (SQLite) and cannot oversell under concurrency.
                $reserved = TicketType::whereKey($typeId)->whereRaw('sold + ? <= quota', [$quantity])->increment('sold', $quantity);

                if ($reserved === 0) {
                    // Throwing rolls back the increments of earlier lines too.
                    throw ValidationException::withMessages(['quantities' => "Sisa kuota {$types[$typeId]->name} tidak mencukupi."]);
                }
            }

            $lines = collect($quantities)->map(fn (int $quantity, int $typeId) => [
                'ticket_type_id' => $typeId,
                'quantity' => $quantity,
                'unit_price' => $types[$typeId]->price,
                'subtotal' => $types[$typeId]->price * $quantity,
            ])->values();

            $order = Order::create([
                'code' => 'ORD-'.Str::upper(Str::ulid()),
                'user_id' => $user->getKey(),
                'event_id' => $types->first()->event_id,
                'total' => $lines->sum('subtotal'),
                'expires_at' => now()->addMinutes(Order::RESERVATION_MINUTES),
            ]);
            $order->items()->createMany($lines->all());

            return $order;
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
                foreach ($order->items as $item) {
                    TicketType::whereKey($item->ticket_type_id)->decrement('sold', $item->quantity);
                }
            }

            // Issued inside the same transaction as the status change, so it happens exactly once.
            if ($to === OrderStatus::Paid) {
                foreach ($order->items as $item) {
                    $order->tickets()->createMany(array_map(fn () => [
                        'event_id' => $order->event_id,
                        'ticket_type_id' => $item->ticket_type_id,
                        'code' => 'TKT-'.Str::upper(Str::random(10)),
                    ], range(1, $item->quantity)));
                }
            }

            $order->refresh();

            return true;
        });
    }

    /** Apply a trusted gateway status (verified webhook or server-side status call). Returns whether the order changed. */
    public function applyUpdate(PaymentUpdate $update): bool
    {
        $order = Order::firstWhere('code', $update->orderCode);

        if ($order === null) {
            Log::warning('Payment update for unknown order', ['order' => $update->orderCode]);

            return false;
        }

        if ($update->grossAmount !== $order->total) {
            Log::error('Payment amount mismatch', ['order' => $order->code, 'expected' => $order->total, 'got' => $update->grossAmount]);

            return false;
        }

        $gatewayFields = array_filter([
            'payment_type' => $update->paymentType,
            'gateway_transaction_id' => $update->transactionId,
        ]);

        $changed = match ($update->status) {
            OrderStatus::Paid => $this->transition($order, OrderStatus::Paid, attributes: ['paid_at' => now(), ...$gatewayFields]),
            OrderStatus::Expired, OrderStatus::Cancelled => $this->transition($order, $update->status, attributes: $gatewayFields),
            OrderStatus::Refunded => $this->transition($order, OrderStatus::Refunded, [OrderStatus::Paid], $gatewayFields),
            OrderStatus::Pending => false,
        };

        if (! $changed && $update->status === OrderStatus::Paid && $order->refresh()->status !== OrderStatus::Paid) {
            // shortcut: money received for an order whose seats were already released; needs manual refund until auto-handling exists
            Log::critical('Payment received for non-pending order', ['order' => $order->code, 'status' => $order->status->value]);
        }

        return $changed;
    }

    /**
     * Refund a paid order through the gateway. Returns true when the gateway already reports it refunded,
     * false when it is still processing (a later webhook finishes it). Throws PaymentGatewayException on failure.
     */
    public function refund(Order $order, string $reason): bool
    {
        $update = $this->gateway->refund($order, $reason);

        activity('audit')
            ->performedOn($order)
            ->event('refund')
            ->withProperties(['reason' => $reason, 'amount' => $order->total, 'gateway_status' => $update->transactionStatus])
            ->log('Refund pesanan');

        return $update->status === OrderStatus::Refunded && $this->applyUpdate($update);
    }
}
