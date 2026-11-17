<?php

namespace App\Models;

use App\Enums\OrderStatus;
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
