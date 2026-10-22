@props([
    'href',
    'active' => false,
    'count' => null,
    'variant' => null,
])

<li class="nav-item">
    <a href="{{ $href }}"
       {{ $attributes->class([
           'nav-link py-1 px-3 rounded-pill d-inline-flex align-items-center gap-2 small',
           'active fw-semibold shadow-sm' => $active && $variant !== 'danger',
           'active bg-danger text-white fw-semibold shadow-sm border-danger' => $active && $variant === 'danger',
           'text-danger bg-danger-subtle border border-danger-subtle' => !$active && $variant === 'danger',
           'text-body bg-body border' => !$active && $variant !== 'danger',
       ]) }}>
        <span>{{ $slot }}</span>
        @if ($count !== null)
            <span class="badge {{ $active ? ($variant === 'danger' ? 'bg-white text-danger' : 'bg-light text-dark') : ($variant === 'danger' ? 'bg-danger text-white' : 'bg-secondary-subtle text-body-secondary border') }} rounded-pill" style="font-size: 0.75rem;">
                {{ $count }}
            </span>
        @endif
    </a>
</li>

