# Toastify

Toastify flashes Bootstrap toast notifications — small pop-up messages with success, error, info, and warning variants. You can flash one from a controller so it appears after a redirect, push one live from a Livewire component, or fire one straight from JavaScript.

## Quick start

Toastify CSS and JavaScript are included automatically whenever you render `<x-toasts />` in your layout. The scaffold layout already includes it, so dashboard and website pages work out of the box.

If you build a custom layout, add the component before `</body>`:

```blade
<x-toasts />
```

Then flash a toast with the `toastify()` helper:

```php
toastify()->success(__('Saved successfully'));
```

## Flashing a toast

Call the type you want on the `toastify()` helper. Each takes a title, optional message, and optional per-toast settings:

```php
toastify()->success(__('Saved successfully'));
toastify()->error(__('Something went wrong'), options: ['delay' => 5000]);
toastify()->info(__('Heads up'), __('The import is still running.'));
toastify()->info(__('Heads up'));
toastify()->warning(__('Check your input'));
toastify()->toast(__('A plain toast'));
```

When the message is empty, the title is rendered as the toast body without a header.

Flashed toasts are stored in the session, so they survive a redirect and show on the next page.

## Examples

### From a controller, after a redirect

```php
toastify()->success(__('Post ":title" published', ['title' => $post->title]));

return redirect()->route('posts.index');
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

The page exposes a global `toastify()` you can call from inline scripts. Each call takes the title, optional message, and optional settings:

```js
toastify().error('Please enter a title before saving');
toastify().success('Saved', 'Your changes were saved.', { delay: 5000 });
```

## Customizing toast types

The available types and their styling live in `config/toastify.php`. Publish it to add or restyle types:

```bash
php artisan vendor:publish --tag=toastify::config
```

- **`toastifiers`** — the map of toast types. Out of the box: `toast`, `error`, `success`, `info`, and `warning`, each with an icon and optional icon color. Add a key here to create a new type — it becomes callable as `toastify()->yourType(...)` and from JavaScript.
- **`defaults`** — settings applied to every toast, such as its on-screen position, delay, and whether it shows a close button.

## Notes

- A toast's type must exist in `toastifiers` to pick up a preset style; an unknown type still shows but unstyled.
- Include `<x-toasts />` once per page — it loads the assets and renders session toasts.

## Related

- [Asset & Init System](/frontend/asset-system)
- [Components overview](/components/overview)
