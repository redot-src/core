<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <label {{ $attributes->only('class')->class(['form-check form-switch']) }}>
        <input type="checkbox" id="{{ $id }}" @checked($value)
            {{ $attributes->except('class')->class(['form-check-input']) }} />
        <span class="form-check-label form-check-label-on">{{ $on ?: __('Enabled') }}</span>
        <span class="form-check-label form-check-label-off">{{ $off ?: __('Disabled') }}</span>
    </label>
</x-field>
