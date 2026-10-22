@props([
    'title' => null,
    'header' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'card shadow-sm border']) }}>
    @if ($header || $title)
        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
            @if ($title)
                <h2 class="h5 mb-0 fw-semibold">{{ $title }}</h2>
            @endif
            {{ $header }}
        </div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="card-footer bg-transparent py-3 border-top">
            {{ $footer }}
        </div>
    @endif
</div>
