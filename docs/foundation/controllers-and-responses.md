# Controllers & API Responses

Extend the Redot base controller in your dashboard controllers to get two
ready-made response styles: redirect-with-flash helpers for server-rendered
(Blade) flows, and consistent JSON envelopes for API endpoints. Authorization
(`$this->authorize(...)`) and validation (`$this->validate(...)`) are wired in
too, so your controllers stay thin.

## Usage

Extend the base controller and use its helpers:

```php
use Redot\Http\Controllers\Controller;

class PostController extends Controller
{
    public function store()
    {
        // ... persist the post ...
        return $this->created(__('Post'), 'posts.index');
    }
}
```

## Redirect + flash helpers

For HTML flows, these return a redirect carrying a flashed message. Each takes an
optional route name (and route parameters) to redirect to; with no route they
redirect `back()`. The flash keys (`success`, `error`, `warning`, `info`) are
what the dashboard's toast layer reads — see [Toastify](/packages/toastify).

The CRUD helpers build a translated message for you (e.g. "Post has been
created.") and flash it as a success:

- **`created`** — confirm a resource was created. Pass the resource label.
- **`updated`** — confirm a resource was updated.
- **`deleted`** — confirm a resource was deleted.
- **`restored`** — confirm a soft-deleted resource was restored.

The generic helpers flash an arbitrary message under their own key:

- **`success`** — a green/positive message.
- **`error`** — a red/failure message; common in guard clauses to bounce back.
- **`warning`** — a cautionary message.
- **`info`** — a neutral, informational message.

## JSON responses

For API endpoints, two helpers emit a consistent envelope (`code`, `success`,
`message`, and an optional `payload`):

```json
{ "code": 200, "success": true, "message": "OK", "payload": {} }
```

- **`respond`** — return a success response. Pass the payload (a model, array, or
  paginator), an optional message, and an optional HTTP status. The `payload`
  key is omitted only when you pass `null` explicitly (handy for message-only
  responses).
- **`fail`** — emit a failure envelope. It *throws*, short-circuiting the
  request, so you do not need to `return` it.

Any exception thrown from an `api/*` route (or a request that expects JSON) is
automatically converted into this same envelope — including the one thrown by
`fail()`. Validation errors surface as a `422` with the field errors in
`payload`; not-found as `404`, auth failures as `401`/`403`, and so on. You
rarely call the underlying converter yourself.

## Examples

### Resource controller (HTML flows)

```php
return $this->created(__('Post'), 'posts.index'); // store()
return $this->updated(__('Post'));                // update() -> back()
return $this->deleted(__('Post'));                // destroy() -> back()
return $this->restored(__('Post'));               // restore() -> back()
```

### Redirecting to a route with a parameter

```php
return $this->success(
    __('Post published successfully.'),
    'posts.show',
    $post,
);
```

### Bouncing back with an error

```php
if ($category->posts()->exists()) {
    return $this->error(__('This category is still in use.'));
}
```

### Returning a paginator as JSON

```php
public function index()
{
    return $this->respond(payload: Post::paginate(columns: ['title']));
}
```

### Returning a model, or a message only

```php
return $this->respond($request->user());

return $this->respond(message: __('Created successfully.'));
```

## Related

- [Toastify](/packages/toastify) — the JS toast layer driven by flash messages.
- [Localization](/foundation/localization) — locale-aware routing and redirects.
