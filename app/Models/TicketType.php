<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'price', 'quota', 'sale_starts_at', 'sale_ends_at', 'bundle_size', 'description'])]
class TicketType extends Model
{
    /** @use HasFactory<TicketTypeFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'sold' => 0,
        'bundle_size' => 1,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quota' => 'integer',
            'sold' => 'integer',
            'bundle_size' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('audit')
            ->logOnly(['price'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isBundle(): bool
    {
        return $this->bundle_size > 1;
    }

    /** Purchasable units left: a bundle unit needs bundle_size seats. */
    public function availableUnits(): int
    {
        return intdiv($this->remaining(), max(1, $this->bundle_size));
    }

    /** Seats (e-tickets) left. */
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
