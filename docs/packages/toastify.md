# Toastify

Toastify flashes toast notifications in the dashboard — small pop-up messages in styled success, error, info, and warning variants. You can flash one from a controller so it appears after a redirect, push one live from a Livewire component, or fire one straight from JavaScript.

## Quick start

Add the directives to your layout once — the styles in `<head>`, the script before `</body>` (after Livewire and jQuery if you use them):

```blade
{{-- in <head> --}}
@toastifyCss

{{-- before </body> --}}
@toastifyJs
```

Then flash a toast with the `toastify()` helper:

```php
toastify()->success(__('Saved successfully'));
```

## Flashing a toast

Call the type you want on the `toastify()` helper. Each takes a message and optional per-toast settings (for example a longer duration):

```php
toastify()->success(__('Saved successfully'));
toastify()->error(__('Something went wrong'), ['duration' => 5000]);
toastify()->info(__('Heads up'));
toastify()->warning(__('Check your input'));
toastify()->toast(__('A plain toast'));
```

Flashed toasts are stored in the session, so they survive a redirect and show on the next page.

## Examples

### From a controller, after a redirect

```php
toastify()->success(__('Impersonating :name', ['name' => $user->full_name]));

return redirect()->route('website.index');
```

### Live from a Livewire component

Add the `InteractsWithToastify` trait, then call `$this->toastify()` — the toast appears instantly, no page reload:

```php
use Livewire\Component;
use Redot\Toastify\Concerns\InteractsWithToastify;

class SaveProfile extends Component
{
    use InteractsWithToastify;

    public function save(): void
    {
        // ... persist ...
        $this->toastify()->success(__('Saved!'));
    }
}
```

### Straight from JavaScript

The page exposes a global `toastify()` you can call from inline scripts. Each call takes the text and optional settings:

```js
toastify().error('{{ __('Please enter text to generate the QR code') }}');
```

## Customizing toast types

The available types and their styling live in `config/toastify.php`. Publish it to add or restyle types:

```bash
php artisan vendor:publish --tag=toastify::config
```

- **`toastifiers`** — the map of toast types. Out of the box: `toast`, `error`, `success`, `info`, and `warning`, each styled with the dashboard's Tabler color variables. Add a key here to create a new type — it becomes callable as `toastify()->yourType(...)` and from JavaScript.
- **`defaults`** — settings applied to every toast, such as its on-screen position and whether it shows a close button.

## Notes

- A toast's type must exist in `toastifiers` to pick up a preset style; an unknown type still shows but unstyled.
- Include `@toastifyJs` once per page — it clears the session toasts as it renders.

## Related

- [Asset & Init System](/frontend/asset-system)
- [Components overview](/components/overview)
