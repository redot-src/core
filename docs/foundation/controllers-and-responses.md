# Controllers & API Responses

Redot ships a base `Redot\Http\Controllers\Controller` that every application controller extends. It wires in Laravel's authorization and validation traits and adds two complementary response styles: redirect-with-flash helpers for HTML/server-rendered flows, and the `RespondAsApi` trait for consistent JSON envelopes. A global `throw_api_exception` helper turns any thrown exception into that same JSON envelope, and a `FallbackController` handles locale redirects for unmatched routes.

## The base Controller

`Redot\Http\Controllers\Controller` extends Laravel's `Illuminate\Routing\Controller` and composes three traits:

```php
namespace Redot\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Redot\Traits\RespondAsApi;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use RespondAsApi;
    use ValidatesRequests;
}
```

Because `AuthorizesRequests` and `ValidatesRequests` are included, you can call `$this->authorize(...)` and `$this->validate(...)` directly, and because of `RespondAsApi` you get `$this->respond()` / `$this->fail()` (see [JSON responses](#json-responses-with-respondasapi)).

This is the single most-used core symbol in the consumer app — virtually every controller extends it:

```php
use Redot\Http\Controllers\Controller;

class UserController extends Controller
{
    // ...
}
```

### Redirect + flash helpers

For server-rendered flows, the base controller provides helpers that return a `RedirectResponse` carrying a flash message. Each takes an optional route name and route parameters; when `$route` is `null` they redirect `back()`.

The CRUD-style helpers build a translated message for you and flash it under the `success` key:

```php
public function created(string $resource, ?string $route = null, mixed $parameters = [])
public function updated(string $resource, ?string $route = null, mixed $parameters = [])
public function deleted(string $resource, ?string $route = null, mixed $parameters = [])
public function restored(string $resource, ?string $route = null, mixed $parameters = [])
```

`$resource` is interpolated into a translation key, for example `created()` flashes `__(':resource has been created.', ['resource' => $resource])`. The other three use the matching `:resource has been updated. / deleted. / restored.` strings. All flash under `success`.

The generic helpers flash an arbitrary message under the matching session key:

```php
public function success(string|array $message, ?string $route = null, mixed $parameters = [])
public function error(string|array $message, ?string $route = null, mixed $parameters = [])
public function warning(string|array $message, ?string $route = null, mixed $parameters = [])
public function info(string|array $message, ?string $route = null, mixed $parameters = [])
```

`success` flashes under `success`, `error` under `error`, `warning` under `warning`, and `info` under `info`. The `$message` may be a string or an array.

#### Real usage from the consumer

A typical resource controller in the dashboard app:

```php
// app/Http/Controllers/Dashboard/UserController.php
return $this->created(__('User'), 'dashboard.users.index'); // store()
return $this->updated(__('User'));                          // update() -> back()
return $this->deleted(__('User'));                          // destroy() -> back()
return $this->restored(__('User'));                         // restore() -> back()
```

Passing a route + parameter (the second positional arg becomes the route parameter):

```php
// app/Http/Controllers/Dashboard/PublishLanguageTokensController.php
return $this->success(
    __('Language tokens published successfully.'),
    'dashboard.languages.tokens.index',
    $language,
);
```

Guard clauses commonly use `error()` to bounce back with a message:

```php
// app/Http/Controllers/Dashboard/RoleController.php
if (/* role is in use */) {
    return $this->error(__('Role is used by a user.'));
}
```

These flash keys (`success`, `error`, `warning`, `info`) are what the dashboard's flash/toast layer reads. See also [Toastify](/packages/toastify) for the JS toast layer driven by these flashes.

## JSON responses with RespondAsApi

`Redot\Traits\RespondAsApi` standardizes the JSON envelope for API endpoints. It is already used by the base `Controller`, so any controller that extends it can call these methods.

```php
public function respond(mixed $payload = [], string $message = 'OK', int $code = 200): JsonResponse
public function fail(string $message = 'Bad Request', int $code = 400, mixed $payload = []): JsonResponse
```

Both produce the same shape:

```json
{
    "code": 200,
    "success": true,
    "message": "OK",
    "payload": {}
}
```

- `respond()` returns a `JsonResponse` with `success: true` and the given HTTP `$code`. The `payload` key is included unless `$payload` is explicitly `null`.
- `fail()` builds a `success: false` envelope and **throws** an `Illuminate\Http\Exceptions\HttpResponseException` wrapping it — it does not return. This lets you bail out of a method mid-flow. The `payload` key is likewise omitted only when `$payload` is `null`.

### Real usage from the consumer

Returning a paginator as the payload (named arguments are common here):

```php
// app/Http/Controllers/Api/Dashboard/RoleController.php
public function index()
{
    return $this->respond(
        payload: Role::paginate(columns: ['name']),
    );
}
```

Returning only a message (no meaningful payload):

```php
// app/Http/Controllers/Api/Dashboard/AdminController.php
return $this->respond(message: 'Admin created successfully');
```

Returning a model or an arbitrary array:

```php
// app/Http/Controllers/Api/Dashboard/ProfileController.php
return $this->respond($request->user());

// app/Http/Controllers/Api/Website/HomeController.php
return $this->respond([
    // ...
]);
```

## Automatic API exception rendering

You rarely need to call `throw_api_exception` directly — the Redot `Application` registers it as the global exception renderer for JSON requests. In `Redot\Application`:

```php
$exceptions->shouldRenderJsonWhen(fn (Request $request) =>
    $request->expectsJson() || $request->is('api/*'));

$exceptions->render(function (Throwable $e, Request $request) {
    if (! $request->expectsJson() && ! $request->is('api/*')) {
        return null;
    }
    return throw_api_exception($e);
});
```

So any exception thrown from an `api/*` route (or a request that expects JSON) is automatically converted into the standard envelope. This includes the `HttpResponseException` thrown by `$this->fail(...)`.

### `throw_api_exception(Throwable $e): JsonResponse`

The helper maps the exception to an HTTP status code and a human-readable message:

| Exception | Status |
| --- | --- |
| `Symfony\...\HttpException` | its own `getStatusCode()` |
| `ModelNotFoundException` | 404 |
| `ValidationException` | 422 |
| `AuthenticationException` | 401 |
| `AuthorizationException` | 403 |
| anything else | 500 |

Behavior to know:

- The `message` is the exception's own message for any client error (`code < 500`) or whenever `config('app.debug')` is true. For 5xx errors in production it falls back to a generic reason phrase (e.g. `Internal Server Error`).
- The `payload` is the validator errors array for a `ValidationException`; when `app.debug` is on it contains `message`, `file`, `line`, and `trace`; otherwise it is an empty array.
- A wide table of standard reason phrases (400–511) backs the fallback message.

```json
// 422 from a ValidationException
{
    "code": 422,
    "success": false,
    "message": "The email field is required.",
    "payload": { "email": ["The email field is required."] }
}
```

## FallbackController

`Redot\Http\Controllers\FallbackController` is registered by `Redot\Application` as the catch-all route for unmatched URLs:

```php
Route::fallback(FallbackController::class)->middleware('web');
```

Its only job is locale redirection. The `__invoke(Request $request)` handler:

1. Aborts with `404` for any non-`GET`/`HEAD` request.
2. Aborts with `404` unless **both** `redot.routing.append_locale_to_url` and `redot.routing.redirect_non_locale_urls` are enabled.
3. Otherwise, prefixes the current locale (`app()->getLocale()`) to the path and re-matches it against the route table. If that match is itself the fallback route, it aborts `404`.
4. On a real match it issues a `301` redirect to `/{locale}{path}`, preserving the original query string.

The relevant config lives in `config/redot.php`:

```php
'routing' => [
    'append_locale_to_url' => true,
    'redirect_non_locale_urls' => true,
],
```

So with the defaults, hitting `/dashboard/users` when the app locale is `en` results in a `301` to `/en/dashboard/users`. Disable either flag and the fallback simply 404s. See [Localization & Routing](/foundation/localization) for the locale URL strategy.

## `no_content()` helper

`no_content(): string` returns a small muted HTML snippet for "empty" UI states — a `<p class="text-muted">` containing the translated `No content` string. It is meant to be echoed raw in Blade:

```blade
{{-- resources/views/dashboard/memos/show.blade.php --}}
{!! $memo->content ?: no_content() !!}
```

## Gotchas

- `fail()` throws rather than returns — there is no need to `return $this->fail(...)`, though doing so is harmless. The thrown `HttpResponseException` short-circuits the request.
- `respond()` / `fail()` only omit the `payload` key when you pass `null` explicitly; the default `[]` still produces `"payload": {}`.
- The CRUD helpers (`created`/`updated`/`deleted`/`restored`) always flash under `success`; only `error`/`warning`/`info` use other session keys.
- Automatic JSON exception rendering keys off `$request->expectsJson() || $request->is('api/*')`. A non-JSON web request falls through to Laravel's normal exception handling.
- The fallback redirect only fires for `GET`/`HEAD` and requires both routing flags; it issues a permanent `301`.
