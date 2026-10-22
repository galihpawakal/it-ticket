@props([
    'name',
    'value' => null,
])

@php
    $isInvalid = $errors->has($name);
@endphp

<textarea name="{{ $name }}"
          id="{{ $attributes->get('id', $name) }}"
          {{ $attributes->class(['form-control', 'is-invalid' => $isInvalid])->merge([
              'aria-invalid' => $isInvalid ? 'true' : null,
              'aria-describedby' => $isInvalid ? "{$name}-error" : null,
              'rows' => 4,
          ]) }}>{{ old($name, $value ?? $slot) }}</textarea>
