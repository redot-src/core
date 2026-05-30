# Avatar

`<x-avatar>` renders a circular avatar. Give it an `image` and it shows that
image; leave it off and it falls back to the first letter of `name`.

## Usage

```blade
<x-avatar :name="$user->name" :image="$user->avatar" />
```

## Options

- **`name`** — used to derive the single-letter fallback when no `image` is set.
- **`image`** — image URL. When present it's shown instead of the letter.
- **`size`** — size modifier: `sm` (default), `xl`, etc.

Any other attribute or class you pass falls through to the element.

## Examples

### Letter-only avatar

Omit `image` to render the first letter of the name:

```blade
<x-avatar class="flex-shrink-0" :name="$user->name" />
```

### Larger avatar

```blade
<x-avatar size="xl" :name="$user->name" :image="$user->avatar" />
```

### Live preview on file upload

Add an `avatar-preview` marker attribute and wire a file input to the global
`applyAvatarPreview` helper so the avatar updates client-side when a file is
chosen:

```blade
<x-avatar :name="$user->name" :image="$user->avatar" size="xl" avatar-preview />

<x-input type="file" name="avatar" :title="__('Avatar')"
    onchange="applyAvatarPreview(this, '[avatar-preview]')" />
```

## Related

- [Input](/components/input) — the file input used with `applyAvatarPreview`.
- [Components overview](/components/overview) — attribute pass-through.
