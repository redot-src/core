@props(['direction' => null])

@php($direction ??= \Redot\Models\Language::current()?->direction ?? 'ltr')

<link rel="stylesheet" href="{{ hashed_asset("/vendor/redot/libraries/tabler/css/tabler.$direction.min.css") }}" />
<link rel="stylesheet" href="{{ hashed_asset('/vendor/redot/libraries/tabler-icons/tabler-icons.min.css') }}" />
<link rel="stylesheet" href="{{ hashed_asset('/vendor/redot/libraries/glare/glare.min.css') }}" />
<link rel="stylesheet" href="{{ hashed_asset('/vendor/redot/libraries/overlayscrollbars/overlayscrollbars.min.css') }}" />

@stack('plugins-styles')

<link rel="stylesheet" href="{{ hashed_asset('/vendor/redot/libraries/tabler/css/tabler-vendors.min.css') }}" />
<link rel="stylesheet" href="{{ hashed_asset('/vendor/redot/css/redot.css') }}" />
