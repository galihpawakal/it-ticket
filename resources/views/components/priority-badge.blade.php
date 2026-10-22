@props(['priority'])

@php
    use App\Enums\TicketPriority;

    $priorityEnum = $priority instanceof TicketPriority ? $priority : TicketPriority::tryFrom((string) $priority);
    $variant = match ($priorityEnum) {
        TicketPriority::Low => 'secondary',
        TicketPriority::Medium => 'warning',
        TicketPriority::High => 'danger-subtle',
        default => 'secondary',
    };
@endphp

@if ($priorityEnum)
    <x-badge :variant="$variant" {{ $attributes }}>
        {{ $priorityEnum->label() }}
    </x-badge>
@endif
