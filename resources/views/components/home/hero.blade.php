{{-- Hero carousel with stacked side slides. Reference: docs/design/reference/01-beranda.png (top) --}}
@props(['events'])
@php
    $slides = $events->map(fn ($e) => [
        'name' => $e->name,
        'url' => route('events.show', $e),
        'image' => Storage::disk('public')->url($e->banner_path),
    ])->values();
@endphp
@if ($slides->isNotEmpty())
<section aria-roledescription="carousel" aria-label="Event pilihan" class="mb-10"
    x-data="{
        slides: @js($slides),
        active: 0,
        playing: ! window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        hovered: false,
        timer: null,
        offset(i) {
            const n = this.slides.length;
            let d = (i - this.active + n) % n;
            if (d > n / 2) d -= n;
            return d;
        },
        style(i) {
            const d = this.offset(i);
            const layer = [0, 8, 16, 24][Math.abs(d)];
            if (layer === undefined) return 'opacity:0; pointer-events:none; transform: translateX(-50%) scale(.7)';
            const scale = 1 - Math.abs(d) * 0.08;
            return `z-index:${30 - Math.abs(d) * 10}; transform: translateX(calc(-50% + ${Math.sign(d) * layer}%)) scale(${scale})`;
        },
        go(i) { this.active = (i + this.slides.length) % this.slides.length },
        start() { this.timer = setInterval(() => { if (this.playing && ! this.hovered) this.go(this.active + 1) }, 5000) },
    }"
    x-init="start()" x-on:mouseenter="hovered = true" x-on:mouseleave="hovered = false" x-on:focusin="hovered = true" x-on:focusout="hovered = false">
    <div class="relative aspect-[10/3] sm:aspect-[42/10]">
        <template x-for="(slide, i) in slides" :key="i">
            <div class="absolute left-1/2 top-0 h-full w-full origin-center transition-all duration-500 ease-out sm:w-[79%]"
                :style="style(i)" :class="offset(i) === 0 ? '' : 'hidden sm:block'"
                role="group" aria-roledescription="slide" :aria-label="`${i + 1} dari ${slides.length}: ${slide.name}`" :aria-hidden="offset(i) !== 0">
                <a :href="offset(i) === 0 ? slide.url : '#'" :tabindex="offset(i) === 0 ? 0 : -1"
                    x-on:click="if (offset(i) !== 0) { $event.preventDefault(); go(i) }"
                    class="block h-full overflow-hidden rounded-2xl shadow-card focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-600/50">
                    <img :src="slide.image" :alt="slide.name" class="h-full w-full object-cover">
                    <span x-show="offset(i) !== 0" class="absolute inset-0 rounded-2xl bg-brand-50/50"></span>
                </a>
            </div>
        </template>

        <div class="pointer-events-none absolute inset-y-0 left-1/2 z-40 flex w-full -translate-x-1/2 items-center justify-between px-2 sm:w-[79%] sm:-mx-3.5 sm:px-0">
            <button type="button" x-on:click="go(active - 1)" aria-label="Slide sebelumnya"
                class="pointer-events-auto flex h-7 w-7 items-center justify-center rounded-full bg-white text-ink shadow-card hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                <x-heroicon-m-chevron-left class="h-4 w-4" aria-hidden="true" />
            </button>
            <button type="button" x-on:click="go(active + 1)" aria-label="Slide berikutnya"
                class="pointer-events-auto flex h-7 w-7 items-center justify-center rounded-full bg-white text-ink shadow-card hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                <x-heroicon-m-chevron-right class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-center gap-1.5">
        <template x-for="(slide, i) in slides" :key="i">
            <button type="button" x-on:click="go(i)" :aria-label="`Ke slide ${i + 1}`" :aria-current="active === i"
                class="h-1.5 rounded-full transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                :class="active === i ? 'w-4 bg-brand-600' : 'w-1.5 bg-brand-200 hover:bg-brand-300'"></button>
        </template>
        <button type="button" x-on:click="playing = ! playing" class="ml-2 text-gray-500 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
            :aria-label="playing ? 'Jeda slide otomatis' : 'Putar slide otomatis'">
            <x-heroicon-m-pause x-show="playing" class="h-3.5 w-3.5" aria-hidden="true" />
            <x-heroicon-m-play x-show="! playing" x-cloak class="h-3.5 w-3.5" aria-hidden="true" />
        </button>
    </div>
</section>
@endif
