@props(['region'])
{{-- Flat two-tone landmark per region, drawn for this project in the style of the reference. Main shapes use currentColor, accents fill-brand-300. --}}
<svg viewBox="0 0 64 64" fill="currentColor" aria-hidden="true" {{ $attributes }}>
    @switch($region)
        @case(App\Enums\Region::Sumatera)
            {{-- Suspension bridge --}}
            <rect x="15" y="14" width="5" height="34" rx="1"/><rect x="44" y="14" width="5" height="34" rx="1"/>
            <rect x="13" y="12" width="9" height="4" rx="1"/><rect x="42" y="12" width="9" height="4" rx="1"/>
            <path d="M6 38c6-1 11-8 11-22M58 38c-6-1-11-8-11-22M20 17c4 10 8 15 12 15s8-5 12-15" fill="none" stroke="currentColor" stroke-width="1.6"/>
            <path class="fill-brand-300" d="M24 22v10M28 27v5M36 27v5M40 22v10" stroke="currentColor" stroke-width="1"/>
            <rect x="4" y="36" width="56" height="4" rx="1"/>
            <rect class="fill-brand-300" x="6" y="44" width="52" height="3" rx="1.5"/><rect x="2" y="50" width="60" height="3" rx="1.5"/>
            @break
        @case(App\Enums\Region::Jabodetabek)
            {{-- National monument --}}
            <path d="M30 8c0-3 2-5 2-6 0 1 2 3 2 6 0 2-1 3-2 3s-2-1-2-3Z"/>
            <rect x="29" y="12" width="6" height="3" rx="1"/>
            <path d="M29.5 15h5l1.5 25h-8Z"/>
            <path class="fill-brand-300" d="M18 40h28l-6 6H24Z"/>
            <rect x="14" y="46" width="36" height="4" rx="1"/><rect x="10" y="52" width="44" height="3" rx="1.5"/>
            @break
        @case(App\Enums\Region::JawaBarat)
            {{-- Government building with tiered central tower --}}
            <path d="M32 6v6"/><rect x="31" y="5" width="2" height="7"/>
            <path d="M26 18 32 11l6 7Z"/><rect x="27" y="18" width="10" height="6"/>
            <path d="M24 26 32 20l8 6Z"/>
            <path class="fill-brand-300" d="M8 32 20 26h24l12 6Z"/>
            <rect x="10" y="32" width="44" height="16"/>
            <path class="fill-brand-300" d="M14 36h4v8h-4Zm8 0h4v8h-4Zm16 0h4v8h-4Zm8 0h4v8h-4Z"/>
            <path d="M29 48v-7a3 3 0 0 1 6 0v7Z" fill="white" opacity=".6"/>
            <rect x="6" y="48" width="52" height="4" rx="1"/><rect x="4" y="54" width="56" height="3" rx="1.5"/>
            @break
        @case(App\Enums\Region::DiyJateng)
            {{-- Stepped stupa temple --}}
            <rect x="31" y="6" width="2" height="6" rx="1"/>
            <path d="M26 20c0-5 3-8 6-8s6 3 6 8Z"/>
            <rect class="fill-brand-300" x="22" y="20" width="20" height="5" rx="1"/>
            <rect x="18" y="25" width="28" height="5" rx="1"/>
            <rect class="fill-brand-300" x="14" y="30" width="36" height="5" rx="1"/>
            <rect x="10" y="35" width="44" height="6" rx="1"/>
            <path class="fill-brand-300" d="M14 37h2v2h-2Zm6 0h2v2h-2Zm6 0h2v2h-2Zm6 0h2v2h-2Zm6 0h2v2h-2Zm6 0h2v2h-2Z"/>
            <rect x="6" y="41" width="52" height="7" rx="1"/><rect x="2" y="50" width="60" height="3" rx="1.5"/>
            @break
        @case(App\Enums\Region::JawaTimur)
            {{-- City monument: two curved figures on a pedestal --}}
            <path d="M22 40c-4-8-2-20 6-28 0 8 2 14 6 18-6-1-9 3-12 10Z"/>
            <path class="fill-brand-300" d="M42 40c3-8 1-18-5-24 1 7-1 12-5 15 5 0 8 4 10 9Z"/>
            <rect x="18" y="40" width="28" height="4" rx="1"/>
            <path class="fill-brand-300" d="M21 44h3v6h-3Zm6 0h3v6h-3Zm6 0h3v6h-3Zm6 0h3v6h-3Z"/>
            <rect x="16" y="50" width="32" height="3" rx="1"/><rect x="8" y="55" width="48" height="3" rx="1.5"/>
            @break
        @case(App\Enums\Region::Kalimantan)
            {{-- Dayak shield --}}
            <path d="M32 6 41 14v28l-9 8-9-8V14Z"/>
            <path class="fill-brand-300" d="M32 12 37 17v22l-5 5-5-5V17Z"/>
            <path d="M32 18 35 22l-3 4-3-4Zm0 10 3 4-3 4-3-4Z" fill="white" opacity=".7"/>
            <rect x="31" y="50" width="2" height="4"/>
            <rect x="12" y="54" width="40" height="3" rx="1.5"/>
            @break
        @case(App\Enums\Region::Sulawesi)
            {{-- Tongkonan house --}}
            <path d="M4 16c8 6 18 8 28 8s20-2 28-8c-2 6-6 11-12 13H16C10 27 6 22 4 16Z"/>
            <rect class="fill-brand-300" x="16" y="29" width="32" height="12"/>
            <path d="M22 31h4v8h-4Zm8 0h4v8h-4Zm8 0h4v8h-4Z" fill="white" opacity=".6"/>
            <path d="M18 41h3v10h-3Zm8 0h3v10h-3Zm8 0h3v10h-3Zm8 0h3v10h-3Z"/>
            <rect x="8" y="51" width="48" height="3" rx="1.5"/>
            @break
        @case(App\Enums\Region::BaliNusra)
            {{-- Split gate --}}
            <path d="M8 50V30l4-4V18l4-4 4 4v8l4 4v20Z"/>
            <path d="M56 50V30l-4-4V18l-4-4-4 4v8l-4 4v20Z"/>
            <path class="fill-brand-300" d="M12 32h8v4h-8Zm32 0h8v4h-8Zm-32 8h8v4h-8Zm32 0h8v4h-8Z"/>
            <rect class="fill-brand-300" x="26" y="44" width="12" height="6" rx="1"/>
            <rect x="4" y="50" width="56" height="4" rx="1"/><rect x="2" y="56" width="60" height="3" rx="1.5"/>
            @break
    @endswitch
</svg>
