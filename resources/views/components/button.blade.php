@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        'success' => 'btn-success',
        'outline' => 'btn-outline-secondary',
        'outline-primary' => 'btn-outline-primary',
        'outline-danger' => 'btn-outline-danger',
        'light' => 'btn-light border',
        default => 'btn-primary',
    };

    $sizeClass = match ($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };

    $classes = trim("btn {$variantClass} {$sizeClass}");
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes . ($disabled ? ' disabled' : '')]) }} @if($disabled) aria-disabled="true" tabindex="-1" @endif>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @disabled($disabled)>
        {{ $slot }}
    </button>
@endif
