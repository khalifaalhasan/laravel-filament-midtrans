@props(['name', 'value' => 0, 'min' => 0, 'max' => 10, 'label' => 'Jumlah'])
{{-- − n + stepper. Server must re-validate min/max; this only guides the UI. --}}
<div x-data="{ qty: {{ (int) $value }}, min: {{ (int) $min }}, max: {{ (int) $max }} }" {{ $attributes->class('inline-flex items-center gap-3') }}>
    <button type="button" x-on:click="qty = Math.max(min, qty - 1)" x-bind:disabled="qty <= min"
        class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-600 text-white hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 disabled:opacity-40"
        aria-label="Kurangi {{ $label }}">
        <x-heroicon-m-minus class="h-4 w-4" aria-hidden="true" />
    </button>
    <output class="min-w-[1.5rem] text-center text-sm font-medium text-ink" x-text="qty" aria-live="polite" aria-label="{{ $label }}">{{ (int) $value }}</output>
    <input type="hidden" name="{{ $name }}" x-bind:value="qty" value="{{ (int) $value }}">
    <button type="button" x-on:click="qty = Math.min(max, qty + 1)" x-bind:disabled="qty >= max"
        class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-600 text-white hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 disabled:opacity-40"
        aria-label="Tambah {{ $label }}">
        <x-heroicon-m-plus class="h-4 w-4" aria-hidden="true" />
    </button>
</div>
