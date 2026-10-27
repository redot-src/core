@pushOnce('plugins-styles', 'tempus-dominus-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/redot/libraries/tempus-dominus/tempus-dominus.min.css') }}">
@endPushOnce

@pushOnce('plugins-scripts', 'popper-scripts')
    <script src="{{ hashed_asset('/vendor/redot/libraries/popper/popper.min.js') }}"></script>
@endPushOnce

@pushOnce('plugins-scripts', 'tempus-dominus-scripts')
    <script src="{{ hashed_asset('/vendor/redot/libraries/tempus-dominus/tempus-dominus.min.js') }}"></script>
@endPushOnce
