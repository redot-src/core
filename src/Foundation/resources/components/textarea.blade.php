<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <textarea id="{{ $id }}" {{ $attributes->class(['form-control']) }}>{{ $value ?: $slot }}</textarea>
</x-field>
