@props(['href', 'title', 'date', 'image' => null, 'price' => null, 'priceLabel' => null, 'organizer' => null, 'organizerLogo' => null])
{{-- Event card from the reference: poster on a light-blue frame, white body, dashed divider, organizer row.
     price: integer rupiah (cheapest ticket); priceLabel overrides it (e.g. "17 Sesi"). --}}
<a href="{{ $href }}" {{ $attributes->class('group block rounded-card bg-brand-50 p-2 transition hover:shadow-card focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600') }}>
    <div class="aspect-[5/2] overflow-hidden rounded-lg bg-brand-100">
        @if ($image)
            <img src="{{ $image }}" alt="" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-[1.02]">
        @endif
    </div>
    <div class="mt-2 rounded-lg bg-white p-3">
        <h3 class="truncate text-sm font-medium text-ink">{{ $title }}</h3>
        <p class="mt-2 flex items-center gap-1.5 text-xs text-gray-600">
            <x-heroicon-o-calendar-days class="h-4 w-4 text-brand-400" aria-hidden="true" />
            {{ $date }}
        </p>
        <p class="mt-2 text-sm font-semibold text-ink">
            {{ $priceLabel ?? ($price !== null ? rupiah($price) : '') }}
        </p>
        @if ($organizer)
            <div class="mt-3 flex items-center gap-2 border-t border-dashed border-gray-200 pt-3">
                @if ($organizerLogo)
                    <img src="{{ $organizerLogo }}" alt="" class="h-6 w-6 rounded-full object-cover">
                @else
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-[10px] font-semibold text-brand-700" aria-hidden="true">{{ Str::upper(Str::substr($organizer, 0, 1)) }}</span>
                @endif
                <span class="truncate text-xs text-gray-600">{{ $organizer }}</span>
            </div>
        @endif
    </div>
</a>
