@props(['events', 'label'])
{{-- Horizontally scrollable row of event cards with edge arrows. Reference: 01-beranda.png "Rekomendasi Event" --}}
<div class="relative" x-data="{ scroll(dir) { $refs.track.scrollBy({ left: dir * $refs.track.clientWidth, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }) } }">
    <ul x-ref="track" class="-mx-1 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth px-1 pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="{{ $label }}">
        @foreach ($events as $event)
            <li class="w-[80%] shrink-0 snap-start sm:w-[calc(50%-0.5rem)] lg:w-[calc(25%-0.75rem)]">
                <x-event-card :event="$event" class="h-full" />
            </li>
        @endforeach
    </ul>
    @if ($events->count() > 4)
        <button type="button" x-on:click="scroll(-1)" aria-label="Geser {{ $label }} ke kiri"
            class="absolute -left-3 top-1/2 hidden h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full bg-white text-ink shadow-card ring-1 ring-black/5 hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 sm:flex">
            <x-heroicon-m-chevron-left class="h-3.5 w-3.5" aria-hidden="true" />
        </button>
        <button type="button" x-on:click="scroll(1)" aria-label="Geser {{ $label }} ke kanan"
            class="absolute -right-3 top-1/2 hidden h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full bg-white text-ink shadow-card ring-1 ring-black/5 hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 sm:flex">
            <x-heroicon-m-chevron-right class="h-3.5 w-3.5" aria-hidden="true" />
        </button>
    @endif
</div>
