@props(['events'])
{{-- "Laris Manis": fixed-order panels; the active one expands, the rest shrink to strips. Clicking a strip expands it.
     Reference: 01-beranda.png + the user's screen recording of the original interaction. --}}
@php
    $slides = $events->map(fn ($e) => [
        'name' => $e->name,
        'url' => route('events.show', $e),
        'image' => Storage::disk('public')->url($e->poster_path ?? $e->banner_path),
    ])->values();
@endphp
@if ($slides->isNotEmpty())
<section class="mb-12" aria-labelledby="laris-manis-title" x-data="{ active: 0 }">
    <x-ui.section-title centered id="laris-manis-title" :subtitle="'Kumpulan event-event laris manis di '.config('app.name').' yang mungkin kamu sukai'">Laris Manis</x-ui.section-title>

    <ul class="flex h-40 gap-2 sm:h-56 lg:h-64">
        @foreach ($slides as $i => $slide)
            <li class="relative min-w-0 overflow-hidden rounded-xl transition-[flex-grow] duration-500 ease-out motion-reduce:transition-none"
                :class="active === {{ $i }} ? 'grow-[7]' : 'grow'" style="flex-basis: 0">
                <img src="{{ $slide['image'] }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                {{-- Collapsed: a button that expands this panel. Expanded: a link to the event. --}}
                <button type="button" x-show="active !== {{ $i }}" x-on:click="active = {{ $i }}"
                    class="absolute inset-0 focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-brand-600"
                    aria-label="Tampilkan {{ $slide['name'] }}"></button>
                <a href="{{ $slide['url'] }}" x-show="active === {{ $i }}" @if ($i !== 0) x-cloak @endif
                    class="absolute inset-0 focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-brand-600">
                    <span class="absolute bottom-3 left-1/2 max-w-[90%] -translate-x-1/2 truncate rounded-full bg-white/80 px-4 py-1 text-xs font-medium text-brand-900 backdrop-blur">{{ $slide['name'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</section>
@endif
