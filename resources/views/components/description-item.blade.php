@props(['label'])

<dt {{ $attributes->merge(['class' => 'col-sm-3 col-lg-2 text-secondary small fw-semibold']) }}>
    {{ $label }}
</dt>
<dd class="col-sm-9 col-lg-10 mb-0">
    {{ $slot }}
</dd>
