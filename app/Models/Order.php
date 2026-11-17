<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /** Seats are held this long; the gateway charge uses the same expiry. */
    public const RESERVATION_MINUTES = 30;

    /** Most ticket lines (purchase units) one order may hold. */
    public const MAX_TICKETS = 5;

    protected $guarded = ['id'];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'status' => OrderStatus::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'attendees' => 'array',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** One row per seat (e-ticket) in item order: what step 2 asks a holder for. */
    public function seats(): array
    {
        $seats = [];
        foreach ($this->items as $item) {
            foreach (range(1, $item->seats()) as $_) {
                $seats[] = ['ticket_type_id' => $item->ticket_type_id, 'label' => $item->ticketType->name];
            }
        }

        return $seats;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === OrderStatus::Pending && $this->expires_at->isFuture();
    }

    /** "Regular × 2, VIP × 1" */
    public function itemsSummary(): string
    {
        return $this->items->map(fn (OrderItem $item) => "{$item->ticketType->name} × {$item->quantity}")->join(', ');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
