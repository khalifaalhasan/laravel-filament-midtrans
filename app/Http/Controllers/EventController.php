<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::published()
            ->where('ends_at', '>', now())
            ->withMin('ticketTypes as min_price', 'price')
            ->orderBy('starts_at')
            ->paginate(12);

        return view('events.index', compact('events'));
    }

    public function show(Event $event): View
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $event->load(['ticketTypes' => fn ($q) => $q->orderBy('price')]);

        return view('events.show', compact('event'));
    }
}
