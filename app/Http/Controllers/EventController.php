<?php

namespace App\Http\Controllers;

use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Enums\Region;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'featured' => Event::upcoming()->where('is_featured', true)->whereNotNull('banner_path')->orderBy('starts_at')->limit(7)->get(),
            'recommended' => Event::upcoming()->withStartingPrice()->orderByDesc('is_featured')->orderBy('starts_at')->limit(10)->get(),
            'bestSellers' => Event::upcoming()->withSalesTotals()->orderByDesc('tickets_sold')->limit(5)->get()
                ->filter(fn (Event $e) => $e->tickets_sold > 0 && ($e->banner_path || $e->poster_path))->values(),
            'upcoming' => Event::upcoming()->withStartingPrice()->orderBy('starts_at')->limit(8)->get(),
        ]);
    }

    public function explore(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', Rule::enum(EventCategory::class)],
            'wilayah' => ['nullable', Rule::enum(Region::class)],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $events = Event::upcoming()
            ->withStartingPrice()
            // Explicit ESCAPE so % and _ typed by the user match literally on MySQL and SQLite alike.
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->whereRaw("name LIKE ? ESCAPE '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%']))
            ->when($filters['kategori'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['wilayah'] ?? null, fn ($q, $region) => $q->where('region', $region))
            // An event matches a date range when it overlaps it.
            ->when($filters['dari'] ?? null, fn ($q, $from) => $q->where('ends_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['sampai'] ?? null, fn ($q, $to) => $q->where('starts_at', '<=', Carbon::parse($to)->endOfDay()))
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString();

        return view('events.explore', compact('events', 'filters'));
    }

    public function show(Event $event): View
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $event->load(['ticketTypes' => fn ($q) => $q->orderBy('price')]);

        return view('events.show', compact('event'));
    }
}
