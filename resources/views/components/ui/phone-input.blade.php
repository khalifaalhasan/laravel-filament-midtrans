@props(['name' => 'phone', 'label' => 'No. WhatsApp', 'id' => null, 'required' => false, 'value' => null])
{{-- WhatsApp input with Indonesian flag and +62 prefix (reference 05-checkout-detail-pesanan.png). --}}
@php
    $id ??= $name;
    $current = old($name, $value ? preg_replace('/^\+62/', '', $value) : null);
@endphp
<div>
    <x-ui.label :for="$id" :required="$required">{{ $label }}</x-ui.label>
    <div @class([
        'flex overflow-hidden rounded-md border bg-white focus-within:border-brand-600 focus-within:ring-1 focus-within:ring-brand-600',
        'border-gray-200' => ! $errors->has($name),
        'border-danger' => $errors->has($name),
    ])>
        <span class="flex items-center gap-1 border-r border-gray-200 px-3 text-sm" aria-hidden="true">
            <span class="h-3 w-4 overflow-hidden rounded-sm bg-white ring-1 ring-gray-200"><span class="block h-1/2 bg-red-600"></span></span>
            <x-heroicon-m-chevron-up-down class="h-3.5 w-3.5 text-gray-500" />
        </span>
        <span class="flex items-center pl-3 text-sm text-ink">+62</span>
        <input id="{{ $id }}" name="{{ $name }}" type="tel" inputmode="numeric" autocomplete="tel-national" value="{{ $current }}" @required($required)
            @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
            {{ $attributes->class('block w-full border-0 px-2 py-2 text-sm text-ink placeholder:text-gray-500 focus:ring-0') }}>
    </div>
    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
