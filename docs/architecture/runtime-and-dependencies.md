# Runtime & Dependency Build

`Redot\Application` is the custom Laravel application kernel that every Redot dashboard boots from. It wires routing, middleware and exception handling, and — through the **dependency-build pipeline** — compiles language tokens and component initializers into static JavaScript bundles that the front-end loads at runtime. This page explains how the app boots, how those bundles are built, and how the build is enforced and re-triggered.

## The Application kernel

Instead of using Laravel's stock `Illuminate\Foundation\Application`, the consumer app boots from `Redot\Application`, which extends it. The whole bootstrap of a consumer is just:

```php
// bootstrap/app.php (Redot Dashboard)
<?php

use Redot\Application;

$basePath = dirname(__DIR__);

return Application::configure($basePath)->create();
```

`Application::configure()` returns the Laravel `ApplicationBuilder` with everything Redot needs pre-wired, and `create()` builds the application instance.

### What `configure()` wires up

- **Routing** (`->withRouting(...)`): registers API routes (`routes/api/website.php`, `routes/api/dashboard.php`), global web routes (`routes/global.php`), the website/dashboard web routes (`routes/website.php`, `routes/dashboard.php`), and a fallback route (`FallbackController`). Which groups load is driven by `config('redot.features.*')` flags. When running unit tests, all four feature flags are force-enabled. If `config('redot.routing.append_locale_to_url')` is set, the website/dashboard web group is prefixed with a 2-letter `{locale}` segment.
- **Console** (`->withCommands([__DIR__ . '/../routes/console.php'])`): loads the package's console route file.
- **Middleware** (`->withMiddleware(...)`): rebuilds the `web` group, registers the `dashboard` middleware group, and appends API middleware. This is where the dependency-build enforcement is hooked in (see below).
- **Exceptions** (`->withExceptions(...)`): renders JSON for any request that `expectsJson()` or matches `api/*`, delegating to the `throw_api_exception()` helper.

The `web` middleware stack is reordered so that `Localization`, `SubstituteBindings` and finally `EnsureDependenciesBuilt` run as appended middleware:

```php
$middleware->web(remove: [
    SubstituteBindings::class,
]);

$middleware->web(append: [
    Localization::class,
    SubstituteBindings::class,
    EnsureDependenciesBuilt::class,
]);
```

Because `EnsureDependenciesBuilt` is appended to the global `web` group, **every web request** passes through the dependency check.

## What "dependencies" means here

In Redot, "dependencies" are not Composer/npm packages — they are **runtime JavaScript artifacts** generated from PHP-side sources and written under `public/assets/dist`. The `dist_path()` helper resolves that directory:

```php
function dist_path(?string $suffix = null): string
{
    return public_path('assets/dist' . ($suffix ? '/' . $suffix : ''));
}
```

The build produces three kinds of output:

1. **`assets/dist/translations/{locale}.js`** — per-locale translation bundles.
2. **`assets/dist/init.js`** — a single bundle of all component initializers.
3. **`assets/dist/lock.json`** — a manifest of the source files/directories and their `filemtime`, used to detect staleness.

These artifacts expose `window.__locale`, `window.__translations`, and `window.__inits` to the front-end. The dashboard's `public/assets/js/functions.js` reads them directly — e.g. translation lookups check `window.__translations`, and component initializers are invoked via `window.__inits[init](this, options)`.

## The build command: `dependencies:build`

`Redot\Commands\BuildDependenciesCommand` (signature `dependencies:build`, registered in `RedotServiceProvider`) runs three steps:

```php
public function handle()
{
    $this->buildLanguageFiles();
    $this->buildInitFiles();
    $this->buildLockFile();

    info('Dependencies built successfully!');
}
```

### Language files

For each locale in `config('app.locales')`, the command:

- reads `lang/{locale}.json` (JSON-based translations),
- merges every `lang/{locale}/*.php` file, flattened with `Arr::dot()` and keyed by the file's basename (so `auth.php`'s `failed` key becomes `auth.failed`),
- writes a JS file to `dist_path('translations')/{locale}.js`:

```js
window.__locale = 'en';
window.__translations = { "auth.failed": "These credentials do not match our records.", /* ... */ };
```

Each JSON file, each PHP file, and the `lang` directory itself are registered as build dependencies.

### Init files

Every `public/assets/inits/*.js` file is wrapped in an IIFE and assigned onto `window.__inits`, keyed by the file's basename, then written to `dist_path()/init.js`:

```js
window.__inits = {};
window.__inits["tomselect"] = (() => { /* contents of tomselect.js */ })();
window.__inits["query-builder"] = (() => { /* contents of query-builder.js */ })();
```

In the Redot Dashboard, this directory contains initializers such as `coloris.js`, `icon-picker.js`, `query-builder.js`, `repeater.js`, `sortable.js`, `tempus-dominus.js`, `tinymce.js`, `tomselect.js`, `turnstile.js` and `uploader.js`. The `public/assets/inits` directory and each `*.js` file are registered as build dependencies.

### Lock file

`buildLockFile()` deletes any existing `dist_path('lock.json')` and rewrites it from the collected dependencies. Each path is stored relative to `base_path()` with its `filemtime` as the value:

```json
{
    "files": {
        "/lang/en.json": 1779746241,
        "/lang/en/auth.php": 1779649304,
        "/public/assets/inits/tomselect.js": 1779797591
    },
    "directories": {
        "/lang": 1779746241,
        "/public/assets/inits": 1779802170
    }
}
```

This manifest is what the middleware compares against on each request.

## Enforcement: `EnsureDependenciesBuilt` middleware

`Redot\Http\Middleware\EnsureDependenciesBuilt` runs on every web request and guarantees the dist artifacts exist and are fresh:

```php
public function handle(Request $request, Closure $next): Response
{
    $lockFile = dist_path('lock.json');

    if (! file_exists($lockFile)) {
        Artisan::call('dependencies:build');

        return $next($request);
    }

    if (file_exists($lockFile)) {
        $lock = json_decode(file_get_contents($lockFile), true);

        foreach ($lock['files'] as $file => $timestamp) {
            $path = base_path($file);

            if (! file_exists($path) || $timestamp !== filemtime($path)) {
                Artisan::call('dependencies:build');
                break;
            }
        }

        foreach ($lock['directories'] as $directory => $timestamp) {
            $path = base_path($directory);

            if (! file_exists($path) || $timestamp !== filemtime($path)) {
                Artisan::call('dependencies:build');
                break;
            }
        }
    }

    return $next($request);
}
```

Behavior:

- **No `lock.json`** → builds immediately, then continues.
- **Lock present** → compares each tracked file/directory's current `filemtime` against the stored timestamp. If any source is missing or has a changed mtime, it rebuilds. A change to a tracked directory's mtime (e.g. adding/removing an init file) also triggers a rebuild.

Because the check runs inline on the web request, the first request after a source change incurs a synchronous `dependencies:build` call.

## Re-triggering the build: `trigger_dependencies_build()`

The `trigger_dependencies_build()` helper does **not** run the build itself — it deletes the whole dist directory so the next web request finds no `lock.json` and rebuilds from scratch:

```php
function trigger_dependencies_build(): void
{
    File::deleteDirectories(dist_path());
}
```

This is the mechanism used after translations change. For example, `Redot\Jobs\PublishLanguageTokens` writes updated language files and then calls it so the JS bundles regenerate:

```php
// src/Jobs/PublishLanguageTokens.php
public function handle(): void
{
    $this->publishJsonBasedTranslations();
    $this->publishFileBasedTranslations();

    $this->language->tokens()->update(['is_published' => true]);

    // Trigger the build of the dependencies.
    trigger_dependencies_build();
}
```

## Usage

Build the dependencies manually (useful in CI/deploy, before the first request):

```bash
php artisan dependencies:build
```

Force a rebuild from application code after changing any tracked source (translations, init scripts):

```php
trigger_dependencies_build();
```

Resolve the dist directory or a file within it:

```php
dist_path();                  // .../public/assets/dist
dist_path('init.js');         // .../public/assets/dist/init.js
dist_path('translations');    // .../public/assets/dist/translations
```

## Gotchas and notes

- **The `web` group only.** Enforcement lives in the `web` middleware group, so API-only routes do not trigger a build.
- **Synchronous first hit.** When sources change, the next web request blocks on `Artisan::call('dependencies:build')`. Run `php artisan dependencies:build` during deploy to avoid that latency on a user request.
- **mtime-based staleness.** Freshness is decided purely by `filemtime`, not content hashing. Touching a file changes its mtime and forces a rebuild; restoring an identical file with a different mtime also rebuilds.
- **`trigger_dependencies_build()` is a no-op until the next request.** It only deletes the dist directory; the actual rebuild happens lazily via the middleware (or your next manual command run).
- **Locales come from `config('app.locales')`.** Only those locales get translation bundles. The dashboard's available locales are configured under `redot.locales` (see [Configuration](/architecture/configuration)).
- **Front-end contract.** Generated bundles set `window.__locale`, `window.__translations`, and `window.__inits`; the dashboard's `functions.js` consumes these globals directly.

See also: [Service Provider](/architecture/service-provider) and [Helpers](/foundation/helpers) for the full helper surface (`dist_path`, `trigger_dependencies_build`, `throw_api_exception`, and more).
