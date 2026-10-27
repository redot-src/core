<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <div id="{{ $id }}" {{ $attributes->only('class') }}>
        @foreach ($options as $key => $label)
            <label @class(['form-check', 'form-check-inline' => $inline])>
                <input type="checkbox" name="{{ $name }}" value="{{ $key }}"
                    @checked(in_array($key, $value)) @disabled(in_array($key, $disabled))
                    @if ($validation && $loop->first) validation="{{ $validation }}" validation-container="#{{ $id }}" @endif
                    title="{{ $label }}" {{ $attributes->except('class')->class(['form-check-input']) }} />
                <span class="form-check-label">{{ $label }}</span>
            </label>
        @endforeach

        {{ $slot }}
    </div>
</x-field>
