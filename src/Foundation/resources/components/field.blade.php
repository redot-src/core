@props([
    'id' => null,
    'title' => null,
    'hint' => null,
    'required' => false,
])

@if ($title)
    <x-label :title="$title" :for="$id" :required="$required" />
@endif

{{ $slot }}

@if ($hint)
    <x-hint class="mt-1">{{ $hint }}</x-hint>
@endif
