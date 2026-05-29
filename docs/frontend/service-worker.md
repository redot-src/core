# Service Worker

The Redot Dashboard ships a small service worker (`public/service-worker.js`) that caches the platform's static vendor and asset files in the browser. It is registered only in production and only when the `service_worker_enabled` setting is on; otherwise any previously installed worker and its caches are torn down.

## What it does

The worker has a single `fetch` event listener that implements a **cache-first** strategy for a fixed allow-list of paths.

```js
self.addEventListener('fetch', function (event) {
    const cachables = ['/vendor', '/assets'];

    if (!cachables.some((cachable) => event.request.url.includes(cachable))) {
        return;
    }

    event.respondWith(
        caches.open('v1').then(async (cache) => {
            let response = await cache.match(event.request);

            // If the response is in the cache, return it
            if (response) return response;

            // Otherwise, fetch the response from the network and cache it
            response = await fetch(event.request);
            cache.put(event.request, response.clone());

            return response;
        }),
    );
});
```

Behavior:

- **Scope filter** — only requests whose URL contains `/vendor` or `/assets` are intercepted. Every other request (HTML pages, API/AJAX calls, etc.) is left untouched and goes straight to the network.
- **Cache name** — a single cache bucket named `v1` is used.
- **Cache-first** — if a matching response already exists in `v1`, it is returned immediately. Otherwise the request hits the network, the response is cloned into the cache, and then returned.

### Gotchas

- There is no `install`, `activate`, or cache-invalidation logic and no expiry. Once a `/vendor` or `/assets` URL is cached under `v1`, that exact response is served until the URL changes or the cache is cleared. The platform relies on `hashed_asset()` (used throughout `scaffold.blade.php`, e.g. `hashed_asset('/assets/css/app.css')`) to produce versioned URLs so updated assets are fetched fresh rather than served stale.
- The cache name is hard-coded to `v1`; bumping the worker does not roll the cache automatically.
- Non-OK network responses are still cached (no status check before `cache.put`).

## Registration and gating

The worker is registered from the dashboard layout `resources/layouts/scaffold.blade.php`, inside the `<head>`. Registration is gated by two conditions:

1. `config('app.env') === 'production'` — never registers outside production.
2. `setting('service_worker_enabled')` — the runtime toggle.

```blade
{{-- Caching using Service Worker --}}
@if (config('app.env') === 'production' && setting('service_worker_enabled'))
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js');
            });
        }
    </script>
@else
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(e => e.forEach(e => e.unregister()));
            caches.keys().then(e => e.forEach(e => caches.delete(e)));
        }
    </script>
@endif
```

- When **both** conditions pass, the worker is registered on the window `load` event.
- In the `@else` branch (non-production, or the setting disabled), the page actively **unregisters** every existing service worker and **deletes all caches**. This guarantees that toggling the setting off, or running locally, leaves no stale cached assets behind.
- The script is wrapped in an `if ('serviceWorker' in navigator)` feature check, so it is a no-op on browsers without service worker support.

## The `service_worker_enabled` setting

The toggle is defined in `config/redot.php` and defaults to enabled:

```php
'service_worker_enabled' => [
    'default' => true,
],
```

Because the default is `true`, the service worker is active out of the box in production. See [Configuration](/architecture/configuration) for how the `setting()` helper and these defaults work.

The setting is exposed to admins as a toggle on the application information settings screen (`resources/views/dashboard/settings/partials/application-information.blade.php`):

```blade
<x-toggle name="service_worker_enabled" :title="__('Service Worker')" :value="setting('service_worker_enabled')" />
```

Turning the toggle off in production causes the next page load to fall into the `@else` branch, which unregisters the worker and clears its caches for visiting clients.
