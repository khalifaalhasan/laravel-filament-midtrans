{{-- Home page. Reference: docs/design/reference/01-beranda.png. Sections are added per task in epic #70. --}}
<x-public-layout>
    <x-home.hero :events="$featured" />

    @if ($recommended->isNotEmpty())
        <section class="mb-12" aria-labelledby="rekomendasi-title">
            <x-ui.section-title id="rekomendasi-title">Rekomendasi Event</x-ui.section-title>
            <x-home.event-row :events="$recommended" label="Rekomendasi Event" />
        </section>
    @endif

    <x-home.best-sellers :events="$bestSellers" />

    <x-home.categories />

    {{-- shortcut: plain upcoming list until the "Event Terdekat" section (#76) replaces it --}}
    <x-ui.section-title>Event mendatang</x-ui.section-title>
    @if ($upcoming->isEmpty())
        <p class="text-gray-600">Belum ada event yang dibuka.</p>
    @else
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($upcoming as $event)
                <li><x-event-card :event="$event" /></li>
            @endforeach
        </ul>
    @endif
</x-public-layout>
