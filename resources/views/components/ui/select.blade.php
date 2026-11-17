@props(['name', 'label' => null, 'id' => null, 'required' => false, 'options' => [], 'value' => null, 'placeholder' => null])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id ??= str_replace('.', '_', $key);
    $selected = (string) old($key, $value);
    $error = $errors->first($key);
@endphp
<div>
    @if ($label)
        <x-ui.label :for="$id" :required="$required">{{ $label }}</x-ui.label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class([
            'block w-full rounded-md border bg-white py-2 pl-3 pr-8 text-sm text-ink focus:border-brand-600 focus:ring-brand-600',
            'border-gray-200' => ! $error,
            'border-danger' => $error,
        ]) }}>
        @if ($placeholder)
            <option value="" disabled @selected($selected === '')>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
