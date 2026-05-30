# Form Card

`<x-form-card>` is the standard create/edit form scaffold for
resources. It wraps [`<x-form>`](/components/form) in a card with a title header,
a body that auto-includes the resource's form partial, and a footer with Back and
submit buttons. Pass an existing `entry` and it switches from create to edit
mode automatically — inferring the action, method, title, and button label.

## Usage

```blade
<x-layouts::dashboard>
    <x-form-card resource="posts" />
</x-layouts::dashboard>
```

For a create page, give it a `resource`. For an edit page, also pass `entry`.
By convention the body is loaded from that resource's form partial.

## Options

- **`resource`** — resource key used to derive the routes and the form partial.
- **`entry`** — the model being edited. When present, switches to edit mode
  (update route, `PUT`, "Update" label).
- **`data`** — extra data passed to the included form partial (e.g. select
  options). `entry` is always available there too.
- **`title`** — card header title. Defaults to "Create" / "Edit".
- **`submit`** — submit button label. Defaults to "Create" / "Update".
- **`action`** — explicit form action URL, overriding route resolution.
- **`method`** — HTTP method, overriding the create/edit default.
- **`route`** — named route used to build the action.
- **`routeParams`** — parameters for the route. For edits, `entry` is appended
  automatically.
- **`back`** — Back link target. Pass `:back="false"` to hide the Back button.
- **`backParams`** — parameters for the back route.

Provide a default slot to replace the auto-included form partial with your own
body. Use the **`header`** slot to add content next to the title.

## Examples

### Edit page

```blade
<x-form-card resource="posts" :entry="$post" />
```

### Passing extra data to the form partial

```blade
<x-form-card resource="posts" :data="['categories' => $categories]" />
```

The `data` array is forwarded to the partial, so `$categories` is available there.

### Header slot

```blade
<x-form-card resource="posts" :entry="$post">
    <x-slot:header>
        <x-translatable-switcher />
    </x-slot:header>
</x-form-card>
```

### Nested resource with a parent route param

```blade
<x-form-card
    resource="categories.posts"
    :entry="$post"
    :back="false"
    :route-params="[$category]"
/>
```

Here `:route-params="[$category]"` supplies the parent route parameter; `$post`
is appended automatically.

## Related

- [Form](/components/form) — the underlying `<x-form>`.
- [Translatable Switcher](/components/translatable-switcher) — common in the header slot.
- [Components overview](/components/overview) — using components and shared conventions.
