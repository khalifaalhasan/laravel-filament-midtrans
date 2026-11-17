@props(['title' => null, 'icon' => null])
{{-- Light-blue outer card with a white inner body (Data Pemesan, Rincian Pesanan). --}}
<section {{ $attributes->class('rounded-card bg-brand-50 p-4') }}>
    @if ($title || isset($header))
        <header class="mb-3 flex items-center justify-between gap-3 px-1">
            <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                @if ($icon)
                    <x-dynamic-component :component="$icon" class="h-5 w-5 text-brand-600" aria-hidden="true" />
                @endif
                {{ $title }}
            </h2>
            {{ $header ?? '' }}
        </header>
    @endif
    <div class="rounded-lg bg-white p-4">
        {{ $slot }}
    </div>
</section>
