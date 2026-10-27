<x-assets.query-builder />

<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <div query-builder-container>
        <input type="hidden" id="{{ $id }}" value="{{ $value }}" {{ $attributes->merge(['init' => 'query-builder']) }} />
        <div query-builder></div>
    </div>
</x-field>
