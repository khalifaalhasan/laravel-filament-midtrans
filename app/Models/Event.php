<?php

namespace App\Models;

use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\Region;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'location', 'starts_at', 'ends_at', 'poster_path', 'status', 'category', 'region', 'organizer_name', 'organizer_logo_path', 'banner_path', 'is_featured'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'draft',
        'category' => 'musik',
        'region' => 'jabodetabek',
        'is_featured' => false,
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => EventStatus::class,
            'category' => EventCategory::class,
            'region' => Region::class,
            'is_featured' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', EventStatus::Published);
    }

    /** Published and not finished yet: what the public site lists. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->published()->where('ends_at', '>', now());
    }

    /** Adds `min_price`: cheapest ticket that still has seats left (null when sold out or no tickets). */
    public function scopeWithStartingPrice(Builder $query): void
    {
        $query->withMin(['ticketTypes as min_price' => fn (Builder $q) => $q->whereColumn('sold', '<', 'quota')], 'price');
    }

    /** "13 Desember 2026", "04 - 05 Desember 2026" or "28 November - 02 Desember 2026" like the reference cards. */
    public function dateRangeLabel(): string
    {
        $start = $this->starts_at;
        $end = $this->ends_at;

        if ($start->isSameDay($end)) {
            return $start->translatedFormat('d F Y');
        }

        return $start->isSameMonth($end)
            ? $start->format('d').' - '.$end->translatedFormat('d F Y')
            : $start->translatedFormat('d F').' - '.$end->translatedFormat('d F Y');
    }

    /** Events this user may manage in the admin panel: all for staff, assigned ones for a PO. */
    public function scopeManageableBy(Builder $query, User $user): void
    {
        if ($user->isStaff()) {
            return;
        }

        $query->whereHas('officers', fn (Builder $q) => $q->whereKey($user->getKey()));
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** Revenue, valid tickets and check-ins; refunded/unpaid orders do not count. */
    public function scopeWithSalesTotals(Builder $query): void
    {
        $paid = fn (Builder $q) => $q->where('status', OrderStatus::Paid);
        $paidTicket = fn (Builder $q) => $q->whereHas('order', $paid);

        $query
            ->withSum(['orders as revenue' => $paid], 'total')
            ->withCount([
                'tickets as tickets_sold' => $paidTicket,
                'tickets as checked_in' => fn (Builder $q) => $paidTicket($q)->whereNotNull('checked_in_at'),
            ]);
    }

    /** Project Officers assigned to this event. */
    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
