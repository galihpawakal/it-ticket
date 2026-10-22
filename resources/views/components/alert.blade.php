@props([
    'type' => 'info',
    'dismissible' => true,
])

@php
    $alertClass = match ($type) {
        'success' => 'alert-success',
        'danger', 'error' => 'alert-danger',
        'warning' => 'alert-warning',
        default => 'alert-info',
    };
@endphp

<div {{ $attributes->merge(['class' => "alert {$alertClass} border shadow-sm " . ($dismissible ? 'alert-dismissible fade show' : '')]) }} role="alert">
    {{ $slot }}

    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    @endif
</div>
