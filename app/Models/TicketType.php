<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'price', 'quota', 'sale_starts_at', 'sale_ends_at'])]
class TicketType extends Model
{
    /** @use HasFactory<TicketTypeFactory> */
    use HasFactory;

    protected $attributes = [
        'sold' => 0,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quota' => 'integer',
            'sold' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function remaining(): int
    {
        return max(0, $this->quota - $this->sold);
    }

    /** Buyable now: event published and inside the sale window. Quota is checked separately under lock at checkout. */
    public function isOnSale(): bool
    {
        return $this->event->status === EventStatus::Published
            && ($this->sale_starts_at === null || $this->sale_starts_at->isPast())
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());
    }
}
