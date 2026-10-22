@props(['name'])

@php
    $isInvalid = $errors->has($name);
@endphp

<select name="{{ $name }}"
        id="{{ $attributes->get('id', $name) }}"
        {{ $attributes->class(['form-select', 'is-invalid' => $isInvalid])->merge([
            'aria-invalid' => $isInvalid ? 'true' : null,
            'aria-describedby' => $isInvalid ? "{$name}-error" : null,
        ]) }}>
    {{ $slot }}
</select>
