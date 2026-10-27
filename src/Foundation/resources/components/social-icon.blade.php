@props([
    'social' => 'google',
    'size' => 'md',
])

<x-assets.tabler-socials />

<span {{ $attributes->class(['social', "social-$size", "social-app-$social"]) }}></span>
