@props(['variant' => 'secondary'])

@php
    $bgClass = match ($variant) {
        'primary' => 'bg-primary-subtle text-primary border border-primary-subtle',
        'success' => 'bg-success-subtle text-success border border-success-subtle',
        'warning' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'danger' => 'bg-danger text-white',
        'danger-subtle' => 'bg-danger-subtle text-danger border border-danger-subtle',
        'info' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        'light' => 'bg-light text-dark border',
        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge rounded-pill fw-medium px-2 py-1 {$bgClass}"]) }}>
    {{ $slot }}
</span>
