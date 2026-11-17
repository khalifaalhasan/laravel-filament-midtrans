@props(['events'])
{{-- "Laris Manis": large centre slide with cropped neighbours. Reference: 01-beranda.png --}}
@php
    $slides = $events->map(fn ($e) => [
        'name' => $e->name,
        'url' => route('events.show', $e),
        'image' => Storage::disk('public')->url($e->banner_path ?? $e->poster_path),
    ])->values();
@endphp
@if ($slides->isNotEmpty())
<section class="mb-12" aria-labelledby="laris-manis-title"
    x-data="{ slides: @js($slides), active: 0, at(d) { const n = this.slides.length; return this.slides[(this.active + d + n) % n] }, go(d) { this.active = (this.active + d + this.slides.length) % this.slides.length } }">
    <x-ui.section-title centered id="laris-manis-title" :subtitle="'Kumpulan event-event laris manis di '.config('app.name').' yang mungkin kamu sukai'">Laris Manis</x-ui.section-title>

    <div class="flex gap-2 sm:gap-2.5" aria-live="polite">
        @if ($slides->count() > 1)
            <button type="button" x-on:click="go(-1)" class="w-[12%] shrink-0 overflow-hidden rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 sm:w-[15%]"
                :aria-label="`Sebelumnya: ${at(-1).name}`">
                <img :src="at(-1).image" alt="" class="h-full w-full object-cover object-right">
            </button>
        @endif

        <a :href="at(0).url" class="relative block aspect-[5/2] min-w-0 flex-1 overflow-hidden rounded-xl focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-600/50">
            <img :src="at(0).image" :alt="at(0).name" class="h-full w-full object-cover">
            <span class="absolute bottom-3 left-1/2 max-w-[90%] -translate-x-1/2 truncate rounded-full bg-white/80 px-4 py-1 text-xs font-medium text-brand-900 backdrop-blur" x-text="at(0).name"></span>
        </a>

        @if ($slides->count() > 2)
            <button type="button" x-on:click="go(1)" class="w-[12%] shrink-0 overflow-hidden rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 sm:w-[15%]"
                :aria-label="`Berikutnya: ${at(1).name}`">
                <img :src="at(1).image" alt="" class="h-full w-full object-cover object-left">
            </button>
        @endif
    </div>
</section>
@endif
