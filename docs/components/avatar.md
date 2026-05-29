# Avatar

`<x-avatar>` renders a circular avatar that shows a background image when one is provided, and falls back to the first letter of the name otherwise. It is a class-based Blade component backed by `App\View\Components\Avatar`.

## What it is

A small presentational component used for user/admin avatars across the dashboard (navbar, profile, select templates). When an `image` is given it is applied as a CSS `background-image`; when it is missing the component prints the first character of `name` instead. The element is marked `aria-hidden="true"` since the avatar is decorative.

## Props

The component class is `App\View\Components\Avatar` (view: `components.avatar`). Its constructor parameters are the public props:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | `?string` | `null` | Used to derive the single-letter fallback (`mb_substr($name, 0, 1)`) when no `image` is set. |
| `image` | `?string` | `null` | Image URL. When present it is rendered as `background-image: url(...)` and the letter fallback is suppressed. |
| `size` | `string` | `'sm'` | Size modifier. Output adds the class `avatar-{size}` (e.g. `avatar-sm`, `avatar-xl`). |

### Rendered markup and classes

The component always applies the `avatar` class plus `avatar-{size}`. When `image` is set it also adds an inline `style` with the background image. Any additional attributes you pass (such as `class`, `avatar-preview`) are merged onto the `<span>` via <span v-pre>`{{ $attributes }}`</span>.

Example output for `<x-avatar size="xl" image="/u/1.jpg" name="Ada" />`:

```html
<span aria-hidden="true" class="avatar avatar-xl" style="background-image: url(/u/1.jpg)"></span>
```

And without an image, `<x-avatar name="Ada" />`:

```html
<span aria-hidden="true" class="avatar avatar-sm">A</span>
```

There are no slots; content is computed from `name`/`image`.

## Usage

Basic avatar with image fallback to initial (navbar):

```blade
<x-avatar :name="$admin->name" :image="$admin->profile_picture" />
```

Letter-only avatar (no image) in a select template:

```blade
<x-avatar class="flex-shrink-0" :name="$item->full_name" />
```

Larger avatar on the unlock screen:

```blade
<x-avatar size="xl" :name="$user->name" :image="$user->profile_picture" />
```

### Live preview on file upload

On the profile edit screen the avatar is given an `avatar-preview` attribute (passed through to the `<span>`) so a paired file input can update it client-side. The file input calls the global `applyAvatarPreview(source, target)` helper, which reads the selected file and sets the avatar's `background-image`, emptying the letter fallback:

```blade
<x-avatar :name="$user->name" :image="$user->profile_picture" size="xl" avatar-preview />

<x-input type="file" name="profile_picture" :title="__('Profile picture')"
    onchange="applyAvatarPreview(this, '[avatar-preview]')" />
```

`applyAvatarPreview` (defined in the committed `functions.js`) uses a `FileReader` to read the chosen file as a data URL, sets it as the target's `background-image`, and clears any text inside it. The `avatar-preview` here is just a plain pass-through attribute used as the CSS selector target; the component itself does not define it.

## Gotchas

- `image` takes priority: when set, the letter fallback is not rendered even if `name` is provided.
- `size` is interpolated directly into the class name (`avatar-{size}`), so it must correspond to a defined CSS size modifier.
- The element is `aria-hidden`, so it contributes no accessible name; ensure surrounding markup conveys the user's name to assistive tech.

## Related

- [Input component](/components/input) — the file input used with `applyAvatarPreview`.
