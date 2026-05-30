# Service Worker

The dashboard can register a small service worker that caches the platform's
static vendor and asset files in the browser, so repeat visits load them from
cache instead of the network. It runs only in production and only while the
`service_worker_enabled` setting is on.

## Enabling it

Flip the **Service Worker** toggle on the application information settings
screen, or set the `service_worker_enabled` setting:

```blade
<x-toggle name="service_worker_enabled" :title="__('Service Worker')" :value="setting('service_worker_enabled')" />
```

It defaults to on, so the cache is active out of the box in production. It never
registers outside production, regardless of the setting.

## What gets cached

The worker only intercepts requests whose URL contains `/vendor` or `/assets` —
the platform's CSS, JS, fonts, and vendor bundles. Everything else (HTML pages,
API and AJAX calls) goes straight to the network, untouched. The strategy is
cache-first: once a file is cached it is served from cache until its URL changes.

This is why the platform references assets through
[`hashed_asset()`](/foundation/helpers) — the versioned URL changes whenever the
file changes, so an updated asset is fetched fresh rather than served stale.

## Turning it off

Turning the toggle off in production makes the next page load actively
unregister the worker and clear its caches for visiting clients, so you don't
leave stale assets behind. Running locally does the same — there is nothing to
clean up by hand.

## Related

- [Asset & Init System](/frontend/asset-system) — `hashed_asset()` and asset versioning.
- [Settings](/foundation/settings) — the `setting()` helper and defaults.
