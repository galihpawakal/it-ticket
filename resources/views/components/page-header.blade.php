@props(['title'])

<div {{ $attributes->merge(['class' => 'd-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4']) }}>
    <div>
        <h1 class="h3 mb-0 fw-bold">{{ $title ?? $slot }}</h1>
    </div>
    @if (isset($actions))
        <div class="d-flex align-items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
