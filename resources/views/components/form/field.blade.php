@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'mb-3']) }}>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="form-label fw-semibold">
            {{ $label }}
            @if ($required)
                <span class="text-danger" title="Wajib diisi">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <div class="form-text text-muted" @if($name) id="{{ $name }}-hint" @endif>
            {{ $hint }}
        </div>
    @endif

    @if ($name)
        @error($name)
            <div class="invalid-feedback d-block" id="{{ $name }}-error">
                {{ $message }}
            </div>
        @enderror

        @if ($name === 'attachments')
            @foreach ($errors->get('attachments.*') as $fileErrors)
                @foreach ($fileErrors as $fileError)
                    <div class="invalid-feedback d-block">
                        {{ $fileError }}
                    </div>
                @endforeach
            @endforeach
        @endif
    @endif
</div>
