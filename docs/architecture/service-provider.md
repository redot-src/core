# Service Provider

`Redot\RedotServiceProvider` is the single entry point that wires the entire `redot/core` package into a Laravel application. It is auto-discovered by Composer, merges configuration, registers the package's Artisan commands and sub-providers, and applies a collection of global boot-time behaviors (Blade directives, the default paginator view, API rate limiting, custom validation rules, JSON casts, locale defaults, and more).

Everything described here happens automatically the moment the package is installed — there is nothing to register manually in the consumer app.

## Lifecycle Overview

The provider follows Laravel's two-phase lifecycle:

- **`register()`** — binds things into the container: merges config, declares publishable assets (config, stubs, migrations), registers Artisan commands, binds the `Sidebar` singleton, and registers the three sub-providers.
- **`boot()`** — runs after all providers are registered and applies global runtime configuration.

## `register()`

```php
public function register(): void
{
    $this->config();
    $this->stubs();
    $this->migrations();

    $this->commands([ /* ... */ ]);

    $this->app->singleton(Sidebar::class, fn () => new Sidebar);
    $this->app->alias(Sidebar::class, 'sidebar');

    $this->app->register(RedotAuthServiceProvider::class);
    $this->app->register(DatatablesServiceProvider::class);
    $this->app->register(LaravelToastifyServiceProvider::class);
}
```

### Config merge and publish

The package config at `config/redot.php` is merged under the `redot` key, so `config('redot.*')` is always available even before publishing. It is also publishable:

```bash
php artisan vendor:publish --tag=redot::config
```

This copies the config to your app's `config/redot.php`. The config holds feature toggles (`redot.features.*`), available `redot.locales`, and routing settings.

### Stubs

The package's stub files (used by the make commands) are published to your app's `stubs/` directory:

```bash
php artisan vendor:publish --tag=redot::stubs
```

These include the scaffolding stubs such as `dashboard.create.stub`, `dashboard.edit.stub`, `dashboard.index.stub`, `dashboard.index-datatable.stub`, `dashboard.show.stub`, and `website.page.stub`.

### Migrations

Migrations are registered with `publishesMigrations()`, so they are loaded automatically and can also be copied into your app:

```bash
php artisan vendor:publish --tag=redot::migrations
```

### Artisan commands

The following commands (all under `Redot\Commands\*`) are registered:

| Command class | Purpose |
| --- | --- |
| `BuildDependenciesCommand` | Build front-end dependencies |
| `ClearUploadsCommand` | Clear orphaned uploads |
| `EntityMakeCommand` | Scaffold a full entity |
| `ExtractLanguageTokensCommand` | Extract translation tokens |
| `LintCommand` | Run the project linter |
| `ModelPopulateCommand` | Populate model data |
| `PublicLinkCommand` | Create the public storage link |
| `PublishLanguageTokensCommand` | Publish language tokens |
| `RevertLanguageTokensCommand` | Revert language tokens |
| `SyncLanguageTokensCommand` | Sync language tokens |
| `SyncPermissionsCommand` | Sync permissions |
| `ViewMakeCommand` | Scaffold a view |

### Sidebar singleton and alias

```php
$this->app->singleton(Sidebar::class, fn () => new Sidebar);
$this->app->alias(Sidebar::class, 'sidebar');
```

A single `Redot\Sidebar\Sidebar` instance is shared for the whole request and aliased to `'sidebar'`, so it can be resolved via the class name, the `sidebar` alias, or the helper. The consumer app builds its navigation against this in `app/sidebar.php`:

```php
use Redot\Sidebar\Sidebar;

return Sidebar::make([ /* items */ ]);
```

See [Sidebar](/packages/sidebar) for the full API.

### Sub-providers

Three feature providers are registered from within `register()`:

- `Redot\Auth\RedotAuthServiceProvider` — see [Auth](/packages/auth/overview)
- `Redot\Datatables\DatatablesServiceProvider` — see [Datatables](/packages/datatables/overview)
- `Redot\Toastify\LaravelToastifyServiceProvider` — see [Toastify](/packages/toastify)

## `boot()`

```php
public function boot(): void
{
    $this->configureAboutCommand();

    $this->configureBlade();
    $this->configurePaginatorView();

    $this->configureApiRateLimiter();
    $this->configureConvertEmptyStringToNull();

    $this->configureDestructiveCommands();
    $this->configureApplicationLocales();

    $this->configureValidationRules();
    $this->configureJsonCast();
}
```

### About command

Adds a **Redot** section to `php artisan about`, reporting the installed `redot/core` version (via `Composer\InstalledVersions::getPrettyVersion('redot/core')`) and the website `https://redot.dev`.

### Blade directives and layout components

Two layout sources are registered:

```php
Blade::anonymousComponentPath(resource_path('layouts'), 'layouts');
Blade::componentNamespace('App\\View\\Layouts', 'layouts');
```

This means anonymous Blade components in `resources/layouts/` and class-based components in `App\View\Layouts` are both available under the `layouts` namespace. The consumer app uses them as `<x-layouts::...>`:

```blade
<x-layouts::website.auth :title="__('Login with Magic Link')">
    {{-- ... --}}
</x-layouts::website.auth>
```

A `@themer` directive is also registered. It pushes the themer script and theme configuration into the `pre-content` stack. The optional expression argument is the JS key the themer reads from (defaults to `theme`); quotes are stripped from it. The theme config comes from `setting('theme')` and the script path from `hashed_asset('assets/js/themer.js')`:

```blade
{{-- resources/layouts/dashboard/base.blade.php --}}
@themer('dashboard-theme')

{{-- resources/layouts/website/base.blade.php --}}
@themer('website-theme')
```

Calling `@themer` (no argument) uses the `theme` key.

### Default paginator view

```php
Paginator::defaultView('components.pagination');
```

All paginated results render with the `components.pagination` view by default — no need to pass a view to `->links()`.

### API rate limiter

Registers the `api` rate limiter at 60 requests per minute, keyed by the authenticated user id (falling back to the request IP for guests):

```php
RateLimiter::for('api', fn (Request $request) =>
    Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
);
```

### ConvertEmptyStringsToNull skip rule

The framework's `ConvertEmptyStringsToNull` middleware is skipped for `PUT` requests whose path matches `*settings*`. This lets settings forms persist empty strings instead of having them coerced to `null`.

### Destructive command protection

```php
DB::prohibitDestructiveCommands(app()->environment('production'));
```

In the `production` environment, destructive database commands (`migrate:fresh`, `db:wipe`, etc.) are prohibited.

### Application locales and URL defaults

`config('app.locales')` is populated from the `Language` model (`code => name`). If the database/table is not yet available (e.g. during installation), it falls back to `config('redot.locales')`:

```php
try {
    config(['app.locales' => Language::pluck('name', 'code')->toArray()]);
} catch (Exception) {
    config(['app.locales' => array_column(config('redot.locales'), 'name', 'code')]);
}

URL::defaults(['locale' => Arr::first(array_keys(config('app.locales')))]);
```

The first locale becomes the default `locale` route parameter via `URL::defaults()`, so localized routes resolve without explicitly passing it.

### Custom validation rules

Two string-form validation rules are registered as extensions, delegating to their rule objects:

- `phone` → `Redot\Rules\Phone` (parameters are forwarded to the rule constructor)
- `captcha` → `Redot\Rules\Captcha`

```php
Validator::extend('phone', fn ($attr, $value, $params) =>
    (new Phone(...$params))->passes($attr, $value));

Validator::extend('captcha', fn ($attr, $value, $params) =>
    (new Captcha(...$params))->passes($attr, $value));
```

This lets you use the rules as simple strings. For example, the consumer app conditionally adds the captcha rule when a Turnstile site key is configured:

```php
...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
```

### JSON cast configuration

Eloquent's `Json` cast (`Illuminate\Database\Eloquent\Casts\Json`) is reconfigured globally so all JSON columns encode/decode consistently:

- **Encode**: arrays are encoded with `JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`; non-arrays pass through unchanged.
- **Decode**: strings are decoded as associative arrays (`JSON_THROW_ON_ERROR`); non-strings pass through unchanged.

The unescaped-unicode flag is what allows non-Latin content (e.g. Arabic locale names) to be stored without `\uXXXX` escaping.

## Gotchas

- The provider is auto-discovered — do not add it to `config/app.php` manually.
- All three sub-providers are registered for you; you never register `RedotAuthServiceProvider`, `DatatablesServiceProvider`, or `LaravelToastifyServiceProvider` directly.
- `app.locales` and the default URL `locale` are resolved at boot. Adding or removing `Language` records changes the available locales on the next request; before the languages table exists, `redot.locales` is the source of truth.
- The `@themer` directive depends on the `setting('theme')` and `hashed_asset()` helpers and a built `assets/js/themer.js` asset being present.
- Destructive DB commands are blocked in `production`; use a non-production environment for `migrate:fresh` and similar.
