@props([
    'name',
    'type' => 'text',
    'value' => null,
])

@php
    $isInvalid = $errors->has($name) || ($name === 'attachments' && $errors->has('attachments.*'));
@endphp

<input type="{{ $type }}"
       name="{{ $name }}"
       id="{{ $attributes->get('id', $name) }}"
       value="{{ $type !== 'file' ? old($name, $value) : null }}"
       {{ $attributes->class(['form-control', 'is-invalid' => $isInvalid])->merge([
           'aria-invalid' => $isInvalid ? 'true' : null,
           'aria-describedby' => $isInvalid ? "{$name}-error" : null,
       ]) }}>
