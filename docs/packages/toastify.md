# Toastify

Toastify is a thin Laravel + Livewire wrapper around the [Toastify JS](https://github.com/apvarun/toastify-js) library. It lets you flash toast notifications from controllers (via session) or push them live from Livewire components (via browser events), with a small set of pre-styled toast types that match the Tabler (`--tblr-*`) CSS variables used by the dashboard.

## Key concepts

There are two ways a toast reaches the browser:

1. **Session flash** — `toastify()->success(...)` pushes a message onto the `toastify` session key. On the next page render, the JS injected by `@toastifyJs` reads `session('toastify')`, displays each toast, and forgets the key.
2. **Livewire dispatch** — inside a Livewire component, `$this->toastify()->success(...)` dispatches a `toastify` browser event. The same JS listens for it via `Livewire.on('toastify', ...)` and shows the toast immediately, without a page reload.

Both paths share the same toast "types" (called *toastifiers*), which are defined in config and rendered into the JS so every toast type stays consistent in style.

## Public surface

### The `toastify()` helper

```php
function toastify(): \Redot\Toastify\Toastify
```

Resolves the singleton `Redot\Toastify\Toastify` instance from the container (also bound under the `toastify` alias).

### `Redot\Toastify\Toastify`

The class exposes its toast methods through `__call`, so each toastifier name from config becomes a method. The documented signatures are:

```php
public function toast(string $message, array $options = []): void
public function error(string $message, array $options = []): void
public function success(string $message, array $options = []): void
public function info(string $message, array $options = []): void
public function warning(string $message, array $options = []): void

public function css(): string // rendered toastify::css view
public function js(): string  // rendered toastify::js view
```

Calling any toast method pushes `['message', 'type', 'options']` onto the `toastify` session key via `Session::push`. The `type` is the method name, so it must match a key under `toastify.toastifiers` in config to be styled (an unknown type still dispatches but uses no preset style).

### `InteractsWithToastify` trait + `LivewireToastifier`

Add the `Redot\Toastify\Concerns\InteractsWithToastify` trait to a Livewire component to get a `toastify()` method that returns a `Redot\Toastify\LivewireToastifier` bound to that component:

```php
public function toastify(): \Redot\Toastify\LivewireToastifier
```

The toastifier forwards calls like `success`/`error`/`info`/`warning`/`toast` to `$component->dispatch('toastify', message: ..., type: $name, options: ...)`.

### Blade directives

Registered in `boot()`:

- `@toastifyCss` — outputs `app('toastify')->css()` (the stylesheet `<link>`).
- `@toastifyJs` — outputs `app('toastify')->js()` (the Toastify script + session/Livewire wiring).

## Configuration

Config lives at `config/toastify.php` and is merged under the `toastify` key. Publish it with:

```bash
php artisan vendor:publish --tag=toastify::config
```

Keys:

- `cdn.js` / `cdn.css` — URLs for the Toastify library assets. Defaults point at `/vendor/toastify/toastify.min.js` and `/vendor/toastify/toastify.min.css`, so the assets are expected to be served locally rather than from a remote CDN.
- `toastifiers` — a map of type name to options passed to `Toastify({...})`. Out of the box: `toast`, `error`, `success`, `info`, `warning`, each with a `style` block using Tabler CSS variables (e.g. `success` uses `var(--tblr-success, #2fb344)`). Add a key here to create a new toast type; it automatically becomes a callable method on the `Toastify` class and a key in the browser's `toastify()` map.
- `defaults` — global Toastify defaults merged into every toast: `gravity` (`toastify-bottom`), `position` (`right`), `close` (`true`).

## Boot-time behavior

The service provider:

- Registers `Toastify` as a **singleton** and aliases it to `toastify`.
- Registers the `@toastifyCss` and `@toastifyJs` Blade directives.
- Loads views under the `toastify::` namespace and merges the config.

The `@toastifyJs` view also registers a global `window.toastify()` function returning the toastifier map, and — when jQuery is present — exposes each toastifier as `$.success(...)`, `$.error(...)`, etc. Note that `@toastifyJs` calls `session()->forget('toastify')` at render time, so include it once per page after your content.

## Usage

### Include the assets in your layout

Place the directives in your layout. From the dashboard's `resources/layouts/scaffold.blade.php`:

```blade
{{-- in <head> --}}
@toastifyCss

{{-- before </body> (after Livewire/jQuery if used) --}}
@toastifyJs
```

### Flash a toast from a controller

From the dashboard's `UserImpersonateController`:

```php
Auth::guard('users')->login($user);
toastify()->success(__('Impersonating :name', ['name' => $user->full_name]));

return redirect()->route('website.index');
```

The toast survives the redirect because it is stored in the session and rendered on the next request.

### Show a toast from a Livewire component

```php
use Livewire\Component;
use Redot\Toastify\Concerns\InteractsWithToastify;

class SaveProfile extends Component
{
    use InteractsWithToastify;

    public function save(): void
    {
        // ... persist ...
        $this->toastify()->success('Saved!');
        $this->toastify()->error('Something went wrong', ['duration' => 5000]);
    }
}
```

This dispatches the `toastify` browser event and the toast appears without a full page reload.

### Trigger a toast directly from JavaScript

Because `@toastifyJs` exposes `window.toastify()`, you can fire toasts from inline scripts. From the dashboard's QR-code page:

```js
return toastify().error('{{ __('Please enter text to generate QR code') }}');
```

```js
toastify().error('{{ __('Failed to generate QR code') }}');
```

Each call accepts `(text, options = {})`, where `options` are merged on top of the toastifier's preset and the global defaults.

## Gotchas

- The `type` you pass (the method name) must exist in `toastify.toastifiers` to get a preset style; the JS only applies a preset when `toastifiers[type]` exists.
- `@toastifyJs` forgets the session key when rendered, so do not render it multiple times expecting repeated toasts.
- The default CDN paths are local (`/vendor/toastify/...`); make sure those asset files are actually served, or point `toastify.cdn.*` at a real CDN.
- The Livewire path requires both the `InteractsWithToastify` trait on the component and `@toastifyJs` on the page (it wires the `Livewire.on('toastify', ...)` listener).
