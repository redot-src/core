@pushOnce('plugins-styles', 'tom-select-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/redot/libraries/tom-select/tom-select.min.css') }}">
@endPushOnce

@pushOnce('plugins-scripts', 'tom-select-scripts')
    <script src="{{ hashed_asset('/vendor/redot/libraries/tom-select/tom-select.complete.min.js') }}"></script>
@endPushOnce
