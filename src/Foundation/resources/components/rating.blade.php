<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <div id="{{ $id }}" style="--star-size: {{ $size }};" {{ $attributes->only('class')->class(['rating-field']) }}>
        @for ($i = $stars; $i > 0; $i--)
            <input type="radio" name="{{ $name }}" id="{{ "$id-$i" }}" value="{{ $i }}" @checked($i == $value)
                @if ($validation && $i === $stars) validation="{{ $validation }}" validation-container="#{{ $id }}" @endif
                {{ $attributes->except('class') }} />

            <label for="{{ "$id-$i" }}">
                <x-icon :icon="$icon" />
            </label>
        @endfor
    </div>
</x-field>
