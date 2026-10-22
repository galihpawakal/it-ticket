@props([
    'href',
    'active' => false,
    'count' => null,
])

<li class="nav-item">
    <a href="{{ $href }}"
       {{ $attributes->class([
           'nav-link py-1 px-3 rounded-pill d-inline-flex align-items-center gap-2 small',
           'active fw-semibold shadow-sm' => $active,
           'text-body bg-body border' => !$active,
       ]) }}>
        <span>{{ $slot }}</span>
        @if ($count !== null)
            <span class="badge {{ $active ? 'bg-light text-dark' : 'bg-secondary-subtle text-body-secondary border' }} rounded-pill" style="font-size: 0.75rem;">
                {{ $count }}
            </span>
        @endif
    </a>
</li>
