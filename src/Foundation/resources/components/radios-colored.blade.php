<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <div id="{{ $id }}" {{ $attributes->only('class') }}>
        @foreach ($options as $key => $color)
            <label class="form-colorinput">
                <input type="radio" name="{{ $name }}" value="{{ $key }}"
                    @checked($key == $value) @disabled(in_array($key, $disabled))
                    @if ($validation && $loop->first) validation="{{ $validation }}" validation-container="#{{ $id }}" @endif
                    {{ $attributes->except('class')->class(['form-colorinput-input']) }} />
                <span class="form-colorinput-color" @style(["background: $color"])></span>
            </label>
        @endforeach

        {{ $slot }}
    </div>
</x-field>
