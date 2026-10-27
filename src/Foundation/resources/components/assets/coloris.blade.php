@pushOnce('plugins-styles', 'coloris-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/redot/libraries/coloris/coloris.min.css') }}">
@endPushOnce

@pushOnce('plugins-scripts', 'coloris-scripts')
    <script src="{{ hashed_asset('/vendor/redot/libraries/coloris/coloris.min.js') }}"></script>
@endPushOnce
