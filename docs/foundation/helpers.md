# Helpers

`redot/core` ships a set of global helper functions (autoloaded via `src/helpers.php`) that cover the most common needs of a Redot dashboard: reading dynamic settings, building permission-aware UI, formatting data, working with assets, and shaping API responses. They are plain global functions in the root namespace, so you can call them anywhere — controllers, Livewire components, Blade views — without importing anything.

## Application & settings

### `setting()`

```php
function setting(?string $key = null, mixed $default = null, bool $fresh = false): mixed
```

Reads a value from the `Redot\Models\Setting` store. With no key it returns the full settings map as a `key => value` array; with a key it delegates to `Setting::get($key, $default, $fresh)`. Pass `$fresh = true` to bypass any cached value.

```blade
<x-input name="facebook_pixel_id" value="{{ setting('facebook_pixel_id') }}" />
<x-checkboxes name="dashboard_locales[]" :value="setting('dashboard_locales', [])" />
```

```php
// In a controller
if (in_array($language->code, setting('website_locales'))) {
    // ...
}
```

### `app_name()`

```php
function app_name(): string
```

Returns the localized application name from the `app_name` setting (keyed by the current locale via `app()->getLocale()`), falling back to `config('app.name')` when the setting has no value for that locale.

```php
$this->title = app_name();
```

```blade
<a href="{{ url('/') }}">{{ app_name() }}</a>
```

### `app_url()`

```php
function app_url(): string
```

Returns the application base URL via `URL::to('/')`. Used internally by [`url_allowed()`](#url-allowed) to decide whether a URL is external.

## Routing & authorization

### `route_from_url()`

```php
function route_from_url(string $url): ?string
```

Resolves a URL string to its matched route **name**, or `null` if no named route matches (or matching throws). Internally builds a request with `Request::create($url)` and matches it against the registered routes.

### `route_allowed()`

```php
function route_allowed(string $route, string $guard = 'admins'): bool
```

Checks whether the authenticated user of the given guard may access a route. Returns `false` when nobody is authenticated. The result is cached forever per `permission.{guard}.{userId}.{route}` key. A route is considered allowed when **no `Spatie\Permission\Models\Permission` exists with that name** (i.e. the route is unprotected) **or** when `Gate::allows($route)` passes.

```php
Action::edit('dashboard.users.edit')->visible(route_allowed('dashboard.users.edit'));
Action::delete('dashboard.users.destroy')->visible(route_allowed('dashboard.users.destroy'));
```

See [Datatables](/packages/datatables/overview) for where this is most commonly used.

### `url_allowed()`

```php
function url_allowed(string $url, string $guard = 'admins'): bool
```

The URL-based counterpart to `route_allowed()`. External URLs (those that do not contain the app host) are always allowed; otherwise it resolves the URL to a route name with `route_from_url()` and defers to `route_allowed()`.

```blade
@if ($create && url_allowed($create))
    <a href="{{ $create }}" class="btn btn-primary">{{ __('Create') }}</a>
@endif
```

> Note: `route_allowed()` caches results with `cache()->rememberForever(...)`, so permission changes for a user are not reflected until that cache key is invalidated.

## API responses

### `throw_api_exception()`

```php
function throw_api_exception(Throwable $e): JsonResponse
```

Converts any throwable into a uniform JSON error response. It maps common exceptions to HTTP status codes — `HttpException` → its status, `ModelNotFoundException` → 404, `ValidationException` → 422, `AuthenticationException` → 401, `AuthorizationException` → 403, everything else → 500 — and pairs each code with a standard reason phrase.

The response body is always:

```json
{
    "code": 422,
    "success": false,
    "message": "...",
    "payload": {}
}
```

Behavior details:

- For client errors (`code < 500`) or when `config('app.debug')` is true, `message` is the exception message (falling back to the standard phrase). For server errors in production it is the generic phrase only.
- `payload` carries validation errors for `ValidationException`; in debug mode it carries `message`, `file`, `line`, and `trace`; otherwise it is empty.

```php
try {
    // ...
} catch (Throwable $e) {
    return throw_api_exception($e);
}
```

## Formatting & display

### `format_phone()`

```php
function format_phone(string $phone, string $country = 'EG'): string
```

Parses and normalizes a phone number to E.164 format using `libphonenumber`. The default region is `EG`.

```php
format_phone('01001234567');        // +201001234567
format_phone('2025550123', 'US');   // +12025550123
```

### `switch_badge()`

```php
function switch_badge(mixed $value, ?string $true = null, ?string $false = null): string
```

Returns an HTML badge reflecting a truthy/falsy value — a green `badge bg-success-lt` for truthy, a red `badge bg-danger-lt` for falsy. Labels default to the translated `Yes` / `No`. The return value is raw HTML, so render it unescaped (`{!! !!}`).

```php
switch_badge($user->is_active);
switch_badge($order->paid, __('Paid'), __('Unpaid'));
```

### `no_content()`

```php
function no_content(): string
```

Returns a muted "No content" paragraph (`<p class="text-muted">...</p>`), translated via `__()`. Handy as a placeholder for empty rich-text fields.

```blade
{!! $memo->content ?: no_content() !!}
```

### `collect_ellipsis()`

```php
function collect_ellipsis($value = [], int $limit = 3, ?string $ellipsis = '...'): Collection
```

Takes the first `$limit` items of a collection/array and, when there are more, appends a translated ellipsis string. The `$ellipsis` text is passed through `__()` with a `count` replacement equal to the number of hidden items, so a translation key like `:count more` receives the remaining count.

```php
collect_ellipsis($tags, 2, ':count more')->implode(', ');
// tag-a, tag-b, 3 more
```

## Assets & build

### `hashed_asset()`

```php
function hashed_asset(string $path, ?bool $secure = null): string
```

Returns the public asset URL with a cache-busting `?v=` query string derived from `md5(filemtime(...))` of the file in `public_path($path)`. If the file does not exist, the plain `asset()` URL is returned with no version suffix.

```blade
<link rel="stylesheet" href="{{ hashed_asset('/assets/css/app.css') }}" />
<script src="{{ hashed_asset('assets/js/dashboard.js') }}"></script>
```

### `dist_path()`

```php
function dist_path(?string $suffix = null): string
```

Returns the path to the build/distribution directory, `public_path('assets/dist')`, optionally with a `/$suffix` appended.

```php
dist_path();                 // .../public/assets/dist
dist_path('init.js');        // .../public/assets/dist/init.js
```

### `trigger_dependencies_build()`

```php
function trigger_dependencies_build(): void
```

Deletes the entire `dist_path()` directory (via `File::deleteDirectories()`), forcing the front-end dependency build artifacts to be regenerated on next request.

## Images

### `is_image()`

```php
function is_image(string $path): bool
```

Returns `true` when the file's MIME type (from `mime_content_type()`) starts with `image/`. Expects a real filesystem path.

```php
if ($config->thumbnail && is_image(public_path($path))) {
    // ...
}
```

### `create_thumbnail()`

```php
function create_thumbnail(string $path, int $width = 100, int $height = 100, int $quality = 85): ?string
```

Generates a thumbnail next to the original image inside a `thumbnails/` subdirectory, named `{filename}-thumb.{ext}`, and returns its path **relative to `public_path()`**. Key behaviors:

- Throws `InvalidArgumentException` if the file is missing, is not an image, or is an unsupported type, and `RuntimeException` on resource/resize/save failures.
- Supports JPEG, PNG, GIF, and WebP. PNG/GIF transparency is preserved.
- Dimensions are clamped to fit within `$width` × `$height` while keeping aspect ratio.
- Returns the cached thumbnail when one already exists and is newer than the source (no regeneration).

```php
$thumbnail = create_thumbnail(public_path($path));        // 100x100
$thumbnail = create_thumbnail(public_path($path), 200, 200, 90);
```

## Data parsing

### `parse_csv()`

```php
function parse_csv(string|array $csv, ?string $separator = ',', ?callable $callback = null): array
```

Normalizes a comma-separated string (or array) into a clean, re-indexed array. Each item is mapped through `$callback` (defaulting to `trim`), then empty values are filtered out and keys reset with `array_values()`.

```php
$this->value = parse_csv($this->value);             // "a, b, c" => ['a', 'b', 'c']
$acceptedTypes = parse_csv($accept);
$ids = array_values(array_filter(parse_csv($request->query('ids')), fn ($id) => filled($id)));
```

## Querying

### `search_model()`

```php
function search_model(Builder|QueryBuilder $query, array $columns = [], ?string $term = null): Builder|QueryBuilder
```

Applies a grouped `LIKE` search across the given columns. The term is trimmed; an empty term returns the query untouched. Columns containing a dot (e.g. `role.name`) are treated as relations: when the query is an Eloquent `Builder` and the model exposes that relation method, it uses `orWhereHas()`; otherwise it falls back to a plain `orWhere()` on the column after the last dot. All conditions are wrapped in a single `where(fn ...)` group so they don't clobber existing constraints.

```php
$query = search_model($query, $columns, sprintf('{%s}', $term));
```

```php
search_model(User::query(), ['name', 'email', 'role.name'], 'admin');
```

## Components

### `component()`

```php
function component(string $name, array $data = []): string|View
```

Renders a Blade component to a string (or `View`) outside the normal `<x-...>` syntax — useful inside Datatable column getters and other PHP contexts. Resolution order:

1. If `$name` is already a class name (`class_exists`), use it directly.
2. Otherwise build a class name under `{AppNamespace}View\Components\` from the dotted/spaced name (e.g. `user.avatar` → `App\View\Components\User\Avatar`).
3. If a matching class exists, instantiate it with `$data` and render via `Blade::renderComponent()`.
4. If no class matches, fall back to rendering the inline view `components.{name}` with `$data`.

```php
->getter(fn ($value, Admin $admin) => component('avatar', [
    'name' => $admin->name,
    'image' => $admin->profile_picture,
]))
```

## Navigation

### `back_or_route()`

```php
function back_or_route(string $route, mixed $parameters = [], bool $absolute = true): string
```

Returns a safe "go back" URL: the previous URL when it exists, differs from the current URL, and belongs to the app (starts with `config('app.url')`, falling back to `request()->root()`); otherwise the named `route($route, $parameters, $absolute)`. This prevents open redirects to external referrers.

```blade
<a href="{{ back_or_route($back, $backParams) }}" class="btn">{{ __('Back') }}</a>
```

## Device detection

### `is_mobile()` / `is_desktop()`

```php
function is_mobile(): bool
function is_desktop(): bool
```

`is_mobile()` inspects the request's `User-Agent` against a mobile-device regex. It returns `false` safely when no request is bound (e.g. console) or the user agent is empty. `is_desktop()` is simply the negation of `is_mobile()`.

```php
if (is_mobile()) {
    // serve a compact layout
}
```

## Related pages

- [Setting model](/foundation/settings) — the backing store for `setting()` and `app_name()`.
- [Datatables](/packages/datatables/overview) — heavy consumer of `route_allowed()` and `component()`.
