@props(['variant' => 'white'])
{{-- Brand logo. white: on blue backgrounds (navbar, login panel). blue: on white (checkout header). Size follows font-size. --}}
<span {{ $attributes->class([
    'inline-flex items-center gap-[0.35em] whitespace-nowrap font-bold tracking-tight leading-none',
    'text-white' => $variant === 'white',
    'text-brand-900' => $variant === 'blue',
]) }}>
    @if ($variant === 'blue')
        <span class="inline-flex items-center justify-center rounded-[0.25em] bg-brand-900 p-[0.12em] text-white">
            <x-brand.mark class="h-[1em] w-[1em]" />
        </span>
    @else
        <x-brand.mark class="h-[1.15em] w-[1.15em]" />
    @endif
    <span>{{ config('app.name') }}</span>
</span>
