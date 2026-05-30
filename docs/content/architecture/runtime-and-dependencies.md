# Runtime & Dependency Build

A `redot/core` app boots on the package's own application kernel, which wires
routing, middleware, and exception handling for you, plus a **dependency-build
pipeline** that compiles translations and component initializers into the static
JavaScript bundles the front-end loads at runtime. This page covers the
requirements, what runs for you, and how to drive the build.

## Requirements

- **PHP** 8.3+
- **Laravel** 13+
- **Livewire** 4.2+

Installing `redot/core` brings its runtime dependencies along — Sanctum, the
Spatie permission package, Intervention Image, the image optimizer, and a
phone-number library — so you don't add them yourself. See
[Installation](/getting-started/installation).

## What boots for you

A `redot/core` app boots from the package's application kernel instead of
Laravel's stock one. That gives you, with no setup:

- **Routing** — the API, global, website, and dashboard route groups plus a
  fallback route. Which groups load is driven by the
  [`redot.features.*`](/architecture/configuration#features) flags, and the
  website/dashboard routes are wrapped in a `{locale}` prefix when
  [locale routing](/architecture/configuration#routing) is enabled.
- **Console** — the package's console routes are loaded.
- **Middleware** — the `web`, `dashboard`, and API middleware are set up,
  including the dependency-build check on every web request.
- **Exceptions** — API requests automatically receive JSON error responses.

## What "dependencies" means here

In Redot, "dependencies" are not Composer or npm packages — they are the
**runtime JavaScript bundles** generated from your PHP-side sources: per-locale
translation files and a single component-initializer bundle, plus a lock file
used to detect when a rebuild is needed. These bundles are what make
translations and JS-backed components available in the browser. See the
[Asset & Init System](/frontend/asset-system) for how the front-end consumes
them.

## Building the bundles

Build manually (do this during deploy, before the first request):

```bash
php artisan dependencies:build
```

You usually don't have to run it during development: a web-request check
notices when a tracked source file changed and rebuilds automatically on the
next request. The trade-off is that the first request after a change pays for a
synchronous build — running the command at deploy time avoids that latency for
users.

When application code changes translations (for example after publishing
language tokens), the build is re-triggered for you, so the bundles regenerate
on the next request.

## Notes

- **Web requests only.** The automatic rebuild check lives in the `web`
  middleware group, so API-only routes never trigger a build.
- **Change detection is mtime-based.** Freshness is decided by file modification
  times, not content hashing — touching a tracked file forces a rebuild.
- **Only configured locales get bundles.** Translation bundles are generated for
  the locales in your active locale list. See
  [Configuration](/architecture/configuration#locales).

## Related

- [Asset & Init System](/frontend/asset-system) — how the bundles load in the browser.
- [Service Provider](/architecture/service-provider) — the rest of what the package sets up.
- [Artisan Commands](/commands/overview) — `dependencies:build` and friends.
