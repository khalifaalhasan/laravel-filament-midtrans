@props(['name', 'label' => null, 'id' => null, 'type' => 'text', 'required' => false, 'value' => null, 'bag' => 'default'])
@php
    $id ??= $name;
    $error = $errors->getBag($bag)->first($name);
@endphp
<div>
    @if ($label)
        <x-ui.label :for="$id" :required="$required">{{ $label }}</x-ui.label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password') value="{{ old($name, $value) }}" @endif @required($required)
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class([
            'block w-full rounded-md border bg-white px-3 py-2 text-sm text-ink placeholder:text-gray-500 focus:border-brand-600 focus:ring-brand-600',
            'border-gray-200' => ! $error,
            'border-danger' => $error,
        ]) }}>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
