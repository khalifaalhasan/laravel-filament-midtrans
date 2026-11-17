<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
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

    public function show(Event $event): View
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $event->load(['ticketTypes' => fn ($q) => $q->orderBy('price')]);

        return view('events.show', compact('event'));
    }
}
