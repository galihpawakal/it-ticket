@props(['status'])

@php
    use App\Enums\TicketStatus;

    $statusEnum = $status instanceof TicketStatus ? $status : TicketStatus::tryFrom((string) $status);
    $variant = match ($statusEnum) {
        TicketStatus::Open => 'primary',
        TicketStatus::InProgress => 'warning',
        TicketStatus::Done => 'success',
        default => 'secondary',
    };
@endphp

@if ($statusEnum)
    <x-badge :variant="$variant" {{ $attributes }}>
        {{ $statusEnum->label() }}
    </x-badge>
@endif
