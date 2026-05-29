# Input

`<x-input>` is the standard text field used across every form in the Redot Dashboard. It wraps a Bootstrap/Tabler `.form-control`, and optionally renders a label, hint text, input-group add-ons, a floating label, and a password show/hide toggle.

## What it is

The component is backed by the class `App\View\Components\Input` (which extends the local `App\View\Components\Component` base) and the view `resources/components/input.blade.php`. The class derives some state at render time:

- An `id` is auto-generated with `uniqid('input-')` when you don't pass one.
- `isPassword` is set to `true` when `type` is `password` (case-insensitive).
- When the input is a password, `flat` is forced to `true` so the toggle button sits flush with the field.
- `required` is derived for the label from the `validation` attribute — if the `validation` string contains the word `required`, the label renders a required marker.

Any extra HTML attributes you pass (`name`, `value`, `placeholder`, `validation`, `disabled`, `readonly`, etc.) are forwarded onto the underlying `<input>` via the attribute bag, with `form-control` merged into the class list.

## Props

Constructor parameters of `App\View\Components\Input`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `uniqid('input-')` | The `id` on the `<input>` and the label's `for`. Auto-generated when omitted. |
| `title` | `?string` | `null` | Label text. Rendered as a `<x-label>` above the field (or as the floating label when `floating` is set). |
| `hint` | `?string` | `null` | Helper text rendered below the field via `<x-hint>`. |
| `prepend` | `?string` | `null` | Text shown in an `.input-group-text` before the field. |
| `append` | `?string` | `null` | Text shown in an `.input-group-text` after the field. |
| `type` | `string` | `'text'` | The HTML input `type`. `password` enables the toggle button. |
| `flat` | `bool` | `false` | Renders the input group as `.input-group-flat` (forced `true` for passwords). |
| `floating` | `bool` | `false` | Uses a Tabler `.form-floating` layout with the label inside. |

Note: there is no explicit `name`/`value`/`placeholder` prop — pass those as plain attributes; they fall through to the `<input>`.

## Behavior

- The component becomes an input group (`.input-group`) automatically when any of `prepend`, `append`, or password mode is active.
- `flat` only takes effect inside an input group (`input-group-flat` requires `isInputGroup && flat`).
- When `floating` is `false` and `title` is set, the label renders before the field. When `floating` is `true`, the `title` is also used as the input's `placeholder` and the label floats inside the `.form-floating` wrapper.
- The label's required marker comes from the `validation` attribute string containing `required`. Pass `validation="required"` (or e.g. `validation="required|email"`) to mark the field.

### Password toggle

When `type="password"`, an eye toggle button is appended and a `togglePasswordField()` script is pushed once to the `scripts` stack. The script uses jQuery to flip the input between `password` and `text` and swaps the `fa-eye` / `fa-eye-slash` icon. It relies on global jQuery and Font Awesome being available.

## Slots

This component has no slots; all content is driven by props and pass-through attributes. (`title`, `hint`, `prepend`, and `append` are string props, not slots.)

## Usage

Basic text field with a label and validation marker:

```blade
<x-input name="name" :title="__('Name')" :value="old('name', $entry?->name)" validation="required" />
```

Email field pre-filled with old input:

```blade
<x-input type="email" name="email" :title="__('Email address')" value="{{ old('email') }}" validation="required|email" />
```

Password field (auto-renders the show/hide toggle and goes flat):

```blade
<x-input type="password" name="password" :title="__('Password')" :placeholder="__('Password')" validation="required" />
```

Prepend add-on (from `resources/views/dashboard/shortened-urls/partials/form.blade.php`):

```blade
<x-input
    name="slug"
    :title="__('Slug')"
    :value="old('slug', $entry?->slug ?? \Illuminate\Support\Str::random(10))"
    :prepend="route('website.shortened-urls.show') . '/'"
/>
```

Disabled, read-only display field:

```blade
<x-input :title="__('Translation Key')" :value="$entry->key" disabled />
```

File input (the `type` falls through to the `<input>`):

```blade
<x-input type="file" name="profile_picture" :title="__('Profile picture')" />
```

## Related

- [Label component](/components/label) — rendered for the `title`/`required` markup.
- [Hint component](/components/hint) — rendered for the `hint` text.
