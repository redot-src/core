{{-- Pushed here rather than through <x-assets.redot-toasts>: stacks keep pushes in order only within the same view depth --}}
@pushOnce('plugins-scripts', 'redot-toasts-scripts')
    <script src="{{ hashed_asset('/vendor/redot/plugins/redot-toasts.js') }}"></script>
@endPushOnce

@pushOnce('plugins-scripts', 'redot-toasts-init')
    <script>
        (() => {
            const config = @json($config);
            const messages = @json($messages);

            const toasts = new RedotToasts(config);

            // Render toasts flashed from the session.
            messages.forEach(({ type,  title, message, options = {} }) => {
                toasts.show(type, title, message, options);
            });
        })();
    </script>
@endPushOnce
