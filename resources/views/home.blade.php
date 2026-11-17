{{-- Home page. Reference: docs/design/reference/01-beranda.png. Sections are added per task in epic #70. --}}
<x-public-layout>
    <x-home.hero :events="$featured" />

    {{-- shortcut: plain upcoming list until the "Event Terdekat" section (#76) replaces it --}}
    <x-ui.section-title>Event mendatang</x-ui.section-title>
    @if ($upcoming->isEmpty())
        <p class="text-gray-600">Belum ada event yang dibuka.</p>
    @else
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($upcoming as $event)
                <li>
                    <x-ui.event-card :href="route('events.show', $event)" :title="$event->name" :date="$event->dateRangeLabel()"
                        :image="$event->poster_path ? Storage::disk('public')->url($event->poster_path) : null"
                        :price="$event->min_price" :organizer="$event->organizer_name"
                        :organizer-logo="$event->organizer_logo_path ? Storage::disk('public')->url($event->organizer_logo_path) : null" />
                </li>
            @endforeach
        </ul>
    @endif
</x-public-layout>
