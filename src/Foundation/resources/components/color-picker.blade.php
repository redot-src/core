<x-assets.coloris />

<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <input type="text" id="{{ $id }}" {{ $attributes->class(['form-control'])->merge(['init' => 'coloris']) }} />
</x-field>
