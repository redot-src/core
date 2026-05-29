# Components Overview

The Redot Dashboard ships a library of Blade components for building admin and website screens — form inputs, layout primitives, display widgets, and third-party integrations. This page explains how that component system is wired: the base `Component` class, the split between class-backed and anonymous components, and how each kind is registered and resolved.

## The base `Component` class

Every class-backed component in `app/View/Components` extends `App\View\Components\Component`, an abstract base that itself extends Laravel's `Illuminate\View\Component`.

```php
abstract class Component extends LaravelComponent
{
    /**
     * Manipulate the component's attributes.
     */
    protected function attributes(callable $callback): void
    {
        $attributes = new ComponentAttributeBag($this->attributes->getAttributes());

        $this->withAttributes($callback($attributes)->getAttributes());
    }
}
```

The only thing it adds over the framework base is a protected `attributes()` helper. It takes a callable, hands it a fresh `ComponentAttributeBag` built from the component's current attributes, and writes whatever the callable returns back onto the component via `withAttributes()`. Subclasses use this to mutate/merge attributes (add classes, default values, etc.) before rendering, without touching the live attribute bag directly.

The base class defines no view and no props of its own — those are declared by each concrete subclass.

## Two kinds of components

The dashboard uses two distinct component styles, living in two directories:

### Class-backed components — `app/View/Components/*`

These are PHP classes (e.g. `Input`, `Select`, `Form`, `Uploader`, `Repeater`) that extend the base `Component` and pair with a Blade view under `resources/components`. The class declares the public API as constructor-promoted properties and `public` fields; the view renders the markup.

For example `App\View\Components\Input` declares its props as constructor arguments and points at its view with `$view`:

```php
class Input extends Component
{
    public string $view = 'components.input';
    public bool $isPassword = false;

    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        public ?string $prepend = null,
        public ?string $append = null,
        public string $type = 'text',
        public bool $flat = false,
        public bool $floating = false,
    ) {}
}
```

Use a class-backed component when there is rendering logic — computed defaults, conditional state, data shaping in `render()`. `Input::render()`, for instance, generates a default `id`, derives `isPassword` from the `type`, and computes whether the field is required from the `validation` attribute.

The classes present in the dashboard:

`Alert`, `Attachments`, `Avatar`, `Captcha`, `Checkboxes`, `ColorPicker`, `DatePicker`, `FileHint`, `Form`, `FormCard`, `IconPicker`, `Input`, `QueryBuilder`, `Radios`, `RadiosColored`, `Rating`, `Repeater`, `RichEditor`, `Select`, `Status`, `Textarea`, `Toggle`, `Translatable`, `Uploader`.

### Anonymous components — `resources/components/*`

These are standalone `.blade.php` files with no PHP class. They declare their public API with `@props([...])` at the top and render inline. They are used for presentational components with little or no logic.

For example `resources/components/empty.blade.php`:

```blade
@props([
    'icon' => 'fas fa-circle-xmark',
    'title' => __('Nothing to show here'),
    'subtitle' => __('Trust me, I\'ve looked everywhere, there\'s nothing here :('),
])

<div {{ $attributes->class(['card']) }}>
    <div class="empty">
        @if ($icon)
            <div class="empty-icon">
                <x-icon :icon="$icon" class="fa-3x" />
            </div>
        @endif
        <p class="empty-title">{{ $title }}</p>
        @if ($subtitle)
            <p class="empty-subtitle text-secondary">{{ $subtitle }}</p>
        @endif
    </div>
</div>
```

Some anonymous components have no `@props` at all and just merge attributes and render the slot, like `resources/components/hint.blade.php`:

```blade
<small {{ $attributes->merge(['class' => 'form-hint']) }}>
    {{ $slot }}
</small>
```

Anonymous-only components (no matching class) include: `countries`, `empty`, `facebook-pixel`, `flag`, `google-analytics`, `hint`, `icon`, `label`, `logo`, `page-header`, `page-loader`, `pagination`, `repeater-card`, `social-icon`, `translatable-switcher`. The remaining files in `resources/components` are the views for the class-backed components above.

## How components are registered and resolved

The dashboard does **not** call `Blade::component` or `Blade::componentNamespace` for these components — they resolve through Laravel's default conventions, plus one view-path tweak.

### Class components

Laravel auto-discovers class components under the `App\View\Components` namespace. A class named `Input` is reachable as `<x-input>`, `FormCard` as `<x-form-card>`, `ColorPicker` as `<x-color-picker>`, and so on (StudlyCase → kebab-case).

### Anonymous components

Laravel resolves an anonymous tag like `<x-empty>` by looking for a `components/empty.blade.php` view under any registered view path. The dashboard's `config/view.php` registers the resources root as a view path:

```php
'paths' => [
    resource_path(),
    resource_path('views'),
],
```

Because `resource_path()` (the `resources/` directory) is a view path, the file `resources/components/empty.blade.php` is the view `components.empty` — exactly where Laravel looks for the `<x-empty>` anonymous component. That single `paths` entry is what makes every file in `resources/components` available as a short `<x-...>` tag without an explicit namespace registration.

### The `layouts` namespace

Layouts are the one place that *is* explicitly namespaced. In the `redot/core` service provider (`Redot\RedotServiceProvider::configureBlade()`):

```php
Blade::anonymousComponentPath(resource_path('layouts'), 'layouts');
Blade::componentNamespace('App\\View\\Layouts', 'layouts');
```

This registers both anonymous layout views (`resources/layouts/*`) and class-backed layouts (`App\View\Layouts`) under the `layouts::` prefix, which is why layouts are used as `<x-layouts::dashboard>`, `<x-layouts::website.auth>`, etc. The same provider also sets the default paginator view to `components.pagination` and registers the `@themer` directive. See [Layouts](/layouts/overview) for details.

## Component categories

### Form inputs

| Tag | Kind | Page |
| --- | --- | --- |
| `<x-input>` | class | [Input](/components/input) |
| `<x-textarea>` | class | [Textarea](/components/textarea) |
| `<x-select>` | class | [Select](/components/select) |
| `<x-checkboxes>` | class | [Checkboxes](/components/checkboxes) |
| `<x-radios>` / `<x-radios-colored>` | class | [Radios](/components/radios) |
| `<x-toggle>` | class | [Toggle](/components/toggle) |
| `<x-date-picker>` | class | [Date Picker](/components/date-picker) |
| `<x-color-picker>` | class | [Color Picker](/components/color-picker) |
| `<x-icon-picker>` | class | [Icon Picker](/components/icon-picker) |
| `<x-rich-editor>` | class | [Rich Editor](/components/rich-editor) |
| `<x-rating>` | class | [Rating](/components/rating) |
| `<x-uploader>` | class | [Uploader](/components/uploader) |
| `<x-attachments>` | class | [Attachments](/components/attachments) |
| `<x-translatable>` | class | [Translatable](/components/translatable) |
| `<x-query-builder>` | class | [Query Builder](/components/query-builder) |
| `<x-repeater>` | class | [Repeater](/components/repeater) |
| `<x-captcha>` | class | [Captcha](/components/captcha) |
| `<x-label>` / `<x-hint>` / `<x-file-hint>` | anon / class | form helpers |

### Layout and structure

| Tag | Kind | Page |
| --- | --- | --- |
| `<x-form>` | class | [Form](/components/form) |
| `<x-form-card>` | class | [Form Card](/components/form-card) |
| `<x-page-header>` | anon | page chrome |
| `<x-page-loader>` | anon | page chrome |
| `<x-pagination>` | anon | pagination view |
| `<x-empty>` | anon | empty state |
| `<x-layouts::*>` | namespaced | [Layouts](/layouts/overview) |

### Display

| Tag | Kind | Page |
| --- | --- | --- |
| `<x-alert>` | class | [Alert](/components/alert) |
| `<x-avatar>` | class | [Avatar](/components/avatar) |
| `<x-status>` | class | [Status](/components/status) |
| `<x-icon>` | anon | icon rendering |
| `<x-flag>` / `<x-countries>` | anon | locale display |
| `<x-logo>` | anon | branding |
| `<x-social-icon>` | anon | social links |
| `<x-translatable-switcher>` | anon | locale switcher |

### Integrations

| Tag | Kind |
| --- | --- |
| `<x-facebook-pixel>` | anon |
| `<x-google-analytics>` | anon |
| `<x-captcha>` | class (Cloudflare Turnstile) |

## Usage

Components are used with the standard `<x-...>` tag syntax. Props are passed as attributes, and PHP expressions use the `:` prefix. A real screen from the dashboard (`resources/views/dashboard/impersonate/users.blade.php`) combines several of them:

```blade
<x-layouts::dashboard>
    @if ($users->count())
        <x-form class="card" :action="route('dashboard.impersonate-users.store')" method="POST">
            <div class="card-header">
                <div class="card-title">{{ __('Impersonate User') }}</div>
            </div>

            <div class="card-body">
                <div class="mb-3">
                    <x-select name="user_id" :title="__('User')" :query="$users" text="full_name"
                        search="full_name, email" template="user" validation="required" />
                </div>
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">{{ __('Impersonate') }}</button>
            </div>
        </x-form>
    @else
        <x-empty icon="fas fa-user-slash" :title="__('No users found')"
            :subtitle="__('You need to create a user first to be able to impersonate him.')" />
    @endif
</x-layouts::dashboard>
```

A simpler form-field example (`resources/views/dashboard/languages/tokens/partials/form.blade.php`):

```blade
<x-input :title="__('Translation Key')" :value="$entry->key" disabled />
<x-input :title="__('New Translation')" name="value" :value="old('value', $entry->value)" />
```

## Gotchas

- **No explicit registration for these components.** Class components rely on Laravel's `App\View\Components` auto-discovery; anonymous components rely on `resource_path()` being a view path in `config/view.php`. Removing that `paths` entry breaks every `<x-...>` anonymous tag.
- **Only `layouts` is namespaced.** All other components use unprefixed tags. Layouts use the `layouts::` prefix and are registered in the `redot/core` service provider, not the dashboard.
- **Class vs. anonymous is a real distinction.** If a tag has computed defaults or `render()` logic, look in `app/View/Components`; otherwise look for a `@props` block in `resources/components`. The matching `resources/components/*.blade.php` file is the *view* for a class component when one exists.
- **Use the base class for attribute manipulation.** When writing a new class-backed component that needs to merge or rewrite attributes, extend `App\View\Components\Component` and use the protected `attributes()` helper rather than mutating `$this->attributes` directly.

## Related pages

- [Layouts](/layouts/overview)
- [Input](/components/input), [Select](/components/select), [Form](/components/form), [Form Card](/components/form-card)
- [Uploader](/components/uploader), [Repeater](/components/repeater)
