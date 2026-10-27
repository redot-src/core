@props([
    'code' => 'eg',
    'size' => 'md',
])

<x-assets.tabler-flags />

<span {{ $attributes->class(['flag', "flag-$size", "flag-country-$code"]) }}></span>
