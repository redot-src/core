@if ($tom)
    <x-assets.tom-select />
@endif

<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <div id="{{ $id }}-container">
        <div class="d-flex align-items-start gap-2">
            <select id="{{ $id }}" validation-container="#{{ $id }}-container" {{ $attributes }}>
                @foreach ($options as $key => $label)
                    <option value="{{ $key }}" @selected(in_array($key, $value))>{{ $label }}</option>
                @endforeach

                {{ $slot }}
            </select>

            {{ $append ?? '' }}
        </div>
    </div>
</x-field>
