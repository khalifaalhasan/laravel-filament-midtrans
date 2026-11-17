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

    <section class="mb-12" aria-labelledby="terdekat-title">
        <x-ui.section-title id="terdekat-title">Event Terdekat</x-ui.section-title>
        @if ($upcoming->isEmpty())
            <p class="text-gray-600">Belum ada event yang dibuka.</p>
        @else
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($upcoming as $event)
                    <li><x-event-card :event="$event" class="h-full" /></li>
                @endforeach
            </ul>
            <div class="mt-6 text-center">
                {{-- shortcut: points at Jelajah once #79 adds the events.explore route --}}
                <x-ui.button variant="soft" :href="Route::has('events.explore') ? route('events.explore') : '#'">Lihat Lainnya</x-ui.button>
            </div>
        @endif
    </section>
</x-public-layout>
