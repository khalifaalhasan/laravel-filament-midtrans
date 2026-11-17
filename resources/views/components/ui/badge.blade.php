@props(['variant' => 'brand'])
{{-- brand: category / On Sale. danger: Sold Out. soft: Bundle (lighter, on white cards). --}}
<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium',
    'bg-brand-100 text-brand-700' => $variant === 'brand',
    'bg-red-50 text-danger' => $variant === 'danger',
    'bg-brand-50 text-brand-700' => $variant === 'soft',
]) }}>{{ $slot }}</span>
