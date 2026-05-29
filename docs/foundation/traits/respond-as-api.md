# RespondAsApi

`Redot\Traits\RespondAsApi` is a small trait that standardizes JSON API responses across Redot controllers. It gives you two helpers — `respond()` for successes and `fail()` for failures — that both emit a consistent envelope (`code`, `success`, `message`, and an optional `payload`).

## Key concepts

The trait is consumed by the base controller `Redot\Http\Controllers\Controller`, so any controller that extends it (the default for Redot API controllers) gets both helpers for free — no `use` statement needed in the controller body.

Both helpers build the same response shape:

```json
{
  "code": 200,
  "success": true,
  "message": "OK",
  "payload": {}
}
```

The `payload` key is only included when the passed payload is **not** `null`. Passing `null` explicitly omits the key entirely (useful for message-only responses).

## Public surface

```php
namespace Redot\Traits;

trait RespondAsApi
{
    public function respond(mixed $payload = [], string $message = 'OK', int $code = 200): JsonResponse;

    public function fail(string $message = 'Bad Request', int $code = 400, mixed $payload = []): JsonResponse;
}
```

### `respond()`

Returns a success `JsonResponse`. The envelope always sets `success` to `true`. The HTTP status is taken from `$code` (defaulting to `200`).

```php
return $this->respond($user, 'User loaded', 200);
```

produces:

```json
{
  "code": 200,
  "success": true,
  "message": "User loaded",
  "payload": { "id": 1, "name": "..." }
}
```

### `fail()`

Builds a failure envelope (`success` is `false`) and **throws** it as an `Illuminate\Http\Exceptions\HttpResponseException`. Because it throws rather than returns, it short-circuits the current request and Laravel renders the JSON response directly — you do not need to `return` it.

```php
$this->fail('Validation failed', 422, $errors);
// nothing after this line in the handler runs
```

produces (with HTTP status 422):

```json
{
  "code": 422,
  "success": false,
  "message": "Validation failed",
  "payload": { "...": "..." }
}
```

Note the argument order differs from `respond()`: `fail()` takes `message` first, then `code`, then `payload`.

## The base Controller

`Redot\Http\Controllers\Controller` extends Laravel's routing controller and composes the trait alongside `AuthorizesRequests` and `ValidatesRequests`:

```php
namespace Redot\Http\Controllers;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use RespondAsApi;
    use ValidatesRequests;
}
```

It also adds redirect-with-flash helpers for non-API (web) flows — `created()`, `updated()`, `deleted()`, `restored()`, `success()`, `error()`, `warning()`, and `info()`. These return redirects with flashed session messages and are not part of the JSON API surface. The resource helpers translate a localized message such as `:resource has been created.`:

```php
public function created(string $resource, ?string $route = null, mixed $parameters = []);
public function success(string|array $message, ?string $route = null, mixed $parameters = []);
public function error(string|array $message, ?string $route = null, mixed $parameters = []);
```

When `$route` is `null` they redirect `back()`; otherwise they redirect to the named route with the given route parameters.

## Usage

API controllers in the consumer app simply extend the base controller and call `respond()`:

```php
namespace App\Http\Controllers\Api\Dashboard;

use Redot\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return $this->respond(
            payload: Role::paginate(columns: ['name']),
        );
    }
}
```

Returning a model directly as the payload:

```php
public function update(Request $request)
{
    $admin = $request->user();
    $admin->fill($request->only('name', 'email'));
    $admin->save();

    return $this->respond($admin);
}
```

A message-only success (named argument, default empty payload):

```php
return $this->respond(message: 'Admin created successfully');
```

You can also use the trait standalone without extending the base controller — for example in an invokable controller that only needs the JSON helpers:

```php
use Redot\Traits\RespondAsApi;

class UploaderController
{
    use RespondAsApi;

    public function __invoke()
    {
        // ...
        return $this->respond($payload);
    }
}
```

## Gotchas

- `fail()` throws — never wrap it in `return`-only logic expecting execution to continue.
- The `payload` key is dropped only when the value is strictly `null`; an empty array `[]` (the default) still emits `"payload": []`.
- `respond()` and `fail()` have different parameter orders. For `fail()`, pass `code` as the second argument and `payload` last.
- For web (Blade/redirect) flows, prefer the base controller's `created()`/`updated()`/`error()` helpers, which flash messages instead of returning JSON.
