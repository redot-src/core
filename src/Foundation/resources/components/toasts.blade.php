<x-assets.redot-toasts />

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
