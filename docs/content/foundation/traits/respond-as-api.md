# RespondAsApi

Add this trait to a controller to emit consistent JSON API responses. It gives
you two helpers — `respond()` for successes and `fail()` for failures — that
produce a uniform envelope (`code`, `success`, `message`, and an optional
`payload`). The [base controller](/foundation/controllers-and-responses) already
includes it, so most controllers get these for free.

## Usage

If you extend the base controller, just call the helpers:

```php
use Redot\Http\Controllers\Controller;

class PostController extends Controller
{
    public function index()
    {
        return $this->respond(payload: Post::paginate(columns: ['title']));
    }
}
```

To use it on a class that doesn't extend the base controller, add the trait
directly:

```php
use Redot\Traits\RespondAsApi;

class UploaderController
{
    use RespondAsApi;

    public function __invoke()
    {
        return $this->respond($payload);
    }
}
```

Every response shares the same shape:

```json
{ "code": 200, "success": true, "message": "OK", "payload": {} }
```

## What the trait gives you

- **`respond`** — return a success response. Pass the payload (a model, array, or
  paginator), an optional message, and an optional HTTP status. The `payload`
  key is omitted only when you pass `null` explicitly — handy for message-only
  responses.
- **`fail`** — emit a failure response. It *throws*, short-circuiting the
  request, so you do not need to `return` it.

## Examples

### Returning a model

```php
return $this->respond($request->user());
```

### A message-only success

```php
return $this->respond(message: __('Post created successfully.'));
```

### Failing mid-flow

```php
$this->fail(__('Validation failed.'), 422, $errors);
// nothing after this line runs
```

## Notes

- **`fail()` throws** — don't write code expecting execution to continue after
  it.
- For HTML/redirect flows, use the base controller's flash helpers
  (`created()`/`updated()`/`error()`/…) instead — see
  [Controllers & API Responses](/foundation/controllers-and-responses).

## Related

- [Controllers & API Responses](/foundation/controllers-and-responses) — the base
  controller and its flash helpers.
