# Form

`<x-form>` is the dashboard's standard `<form>` wrapper. It renders the opening/closing `<form>` tags, injects the CSRF token, spoofs the HTTP method when needed, and stamps a hidden form identifier — so every page-level form in the dashboard and website is built on top of it.

## What it is

A class-based Blade component backed by `App\View\Components\Form` (`app/View/Components/Form.php`), rendering the view `resources/components/form.blade.php`. It extends the project's base `App\View\Components\Component`.

The rendered markup is:

```blade
<form id="{{ $id }}" action="{{ $action }}" method="{{ $formMethod }}" enctype="{{ $enctype }}"
    {{ $attributes }}>
    @csrf

    @if (in_array($method, ['PUT', 'PATCH', 'DELETE']))
        @method($method)
    @endif

    <input type="hidden" name="_form" value="{{ $identifier }}" />

    {{ $slot }}
</form>
```

## Props

All props come from the `Form` constructor:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `null` → `uniqid('form-')` | The form's `id`. When omitted, a unique `form-…` id is generated in `render()`. |
| `method` | `string` | `'GET'` | The intended HTTP verb. Upper-cased in `render()`. Drives both method spoofing and the real `method` attribute. |
| `action` | `string` | `'.'` | The form action URL. Ignored if `route` is set. |
| `route` | `?string` | `null` | A named route. When present, `action` is overwritten with `route($route, $routeParams)`. |
| `routeParams` | `array` | `[]` | Parameters passed to `route()` when `route` is used. |
| `enctype` | `string` | `'multipart/form-data'` | The form encoding type. Defaults to multipart so file uploads work without extra config. |

### Derived (read-only) values

These are computed in `render()` and are not passed by the caller:

- **`formMethod`** — the actual value of the HTML `method` attribute. It is `'GET'` when `method` is `GET`, otherwise `'POST'`. Real verbs like `PUT`/`PATCH`/`DELETE` are sent as `POST` plus a spoofed `@method`.
- **`identifier`** — `base64_encode("{$action}:{$method}")`, emitted as a hidden `<input name="_form">` so the backend can tell which form on a page was submitted.

> Gotcha: `identifier` is computed **before** the `route` → `action` rewrite in `render()`. When you pass `route` instead of `action`, the identifier is based on the default action (`'.'`) rather than the resolved URL.

## Slot and attributes

- **Default slot** — the form body (fields, buttons, etc.) is placed inside the `<form>`.
- **Extra attributes** — anything else you put on `<x-form>` (e.g. `class`, `id`-less marker attributes, `disable-validation`) is forwarded via <span v-pre>`{{ $attributes }}`</span> onto the `<form>` element.

## Client-side validation

`<x-form>` does not register any JS itself. Instead, the global submit handler in the scaffold's `app.js` intercepts submits for every `<form>` on the page and runs the [RedotValidator](/frontend/plugins/redot-validator) engine against fields carrying a `validation` attribute.

To opt a form out of that client-side validation (typical for logout / immediate-submit utility forms), add the `disable-validation` attribute — it is forwarded onto the `<form>` and the submit handler skips it:

```blade
<x-form id="logout-form" :action="route('dashboard.logout')" method="POST"
    class="d-none" disable-validation />
```

## Usage

### Login form (POST)

```blade
<x-form class="card card-md" :action="route('dashboard.login.store')" method="POST">
    <div class="card-body">
        <div class="mb-3">
            <x-input type="email" name="email" :title="__('Email address')"
                placeholder="your@email.com" validation="required|email" />
        </div>
        <div class="mb-3">
            <x-input type="password" name="password" :title="__('Password')" validation="required" />
        </div>
        <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">{{ __('Sign in') }}</button>
        </div>
    </div>
</x-form>
```

### Update form with method spoofing (PUT)

```blade
<x-form class="card" :action="route('dashboard.profile.update')" method="PUT">
    <div class="card-body">
        <x-input type="file" name="profile_picture" :title="__('Profile picture')" />
        {{-- ... --}}
    </div>
</x-form>
```

This renders `method="POST"` on the `<form>` plus a hidden `@method('PUT')` field.

### Utility form opted out of validation

```blade
<x-form id="logout" :action="route('website.logout')" method="POST" class="d-none" disable-validation />
```

## Related

- [RedotValidator plugin](/frontend/plugins/redot-validator) — the submit-time validation engine that reads `validation` and `disable-validation` attributes on this form.
- `<x-form-card>` — a convenience wrapper around `<x-form>` (`resources/components/form-card.blade.php`) that adds a card header/footer and includes a resource's `partials.form`. It forwards `action`/`method`/`class` straight to `<x-form>`.
