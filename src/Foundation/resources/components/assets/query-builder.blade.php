<x-assets.tom-select />
<x-assets.tempus-dominus />

@pushOnce('plugins-styles', 'query-builder-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/redot/libraries/query-builder/query-builder.min.css') }}">
@endPushOnce

@pushOnce('plugins-scripts', 'query-builder-scripts')
    <script src="{{ hashed_asset('/vendor/redot/libraries/query-builder/query-builder.compatibility.js') }}"></script>
    <script src="{{ hashed_asset('/vendor/redot/libraries/query-builder/query-builder.min.js') }}"></script>
@endPushOnce
