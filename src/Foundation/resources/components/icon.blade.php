@props(['icon'])

@if (str_starts_with($icon, '<'))
    {!! $icon !!}
@else
    <i aria-hidden="true" {{ $attributes->class(['icon', $icon]) }}></i>
@endif
