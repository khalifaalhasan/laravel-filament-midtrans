@props(['subtitle' => null, 'centered' => false])
{{-- Left-aligned ink title (Rekomendasi Event) or centered blue title with subtitle (Laris Manis). --}}
<div {{ $attributes->class(['mb-4', 'text-center' => $centered]) }}>
    <h2 @class(['text-base font-semibold', 'text-ink' => ! $centered, 'text-brand-900' => $centered])>{{ $slot }}</h2>
    @if ($subtitle)
        <p class="mt-1 text-xs text-brand-700">{{ $subtitle }}</p>
    @endif
</div>
