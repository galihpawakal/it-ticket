@props([
    'title' => null,
    'description' => null,
    'actionHref' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-5 px-3']) }}>
    <div class="mb-3 text-muted">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-inbox opacity-50" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .11.312V13a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8.5a.5.5 0 0 1 .11-.312zm-1.22 5.5H1v3.5A1 1 0 0 0 2 13h12a1 1 0 0 0 1-1.5V9.063h-2.59a2.5 2.5 0 0 1-4.82 0H3.59z"/>
        </svg>
    </div>

    @if ($title)
        <h3 class="h5 fw-semibold mb-2">{{ $title }}</h3>
    @endif

    <div class="text-muted mb-3">
        {{ $slot->isEmpty() ? $description : $slot }}
    </div>

    @if ($actionHref && $actionLabel)
        <x-button :href="$actionHref" variant="primary" size="sm">
            {{ $actionLabel }}
        </x-button>
    @endif
</div>
