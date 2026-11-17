@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])
{{-- primary: solid blue action (Pilih, Beli Sekarang, Lanjutkan). soft: light pill (Lihat Lainnya). outline: bordered pill. --}}
@php
    $classes = [
        'inline-flex items-center justify-center gap-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        'rounded-md bg-brand-600 px-4 py-2 text-white hover:bg-brand-700' => $variant === 'primary',
        'rounded-full bg-brand-100 px-4 py-1.5 text-brand-700 hover:bg-brand-200' => $variant === 'soft',
        'rounded-full border border-brand-600 px-4 py-1.5 text-brand-700 hover:bg-brand-50' => $variant === 'outline',
    ];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
