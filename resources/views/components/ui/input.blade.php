@props(['name', 'label' => null, 'id' => null, 'type' => 'text', 'required' => false, 'value' => null])
@php($id ??= $name)
<div>
    @if ($label)
        <x-ui.label :for="$id" :required="$required">{{ $label }}</x-ui.label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)
        @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        {{ $attributes->class([
            'block w-full rounded-md border bg-white px-3 py-2 text-sm text-ink placeholder:text-gray-500 focus:border-brand-600 focus:ring-brand-600',
            'border-gray-200' => ! $errors->has($name),
            'border-danger' => $errors->has($name),
        ]) }}>
    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
