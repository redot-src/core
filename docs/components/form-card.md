# Form Card

`<x-form-card>` is the standard create/edit form scaffold for dashboard resources. It wraps the [`<x-form>`](/components/form) component in a Bootstrap-style card with a title header, a body (which auto-includes the resource's form partial), and a footer with Back and submit buttons. It infers the action URL, HTTP method, title, and button label from whether you pass an existing `entry`.

## What it is

A class-based Blade component backed by `App\View\Components\FormCard` (view: `components.form-card`). Its main job is to remove boilerplate from resource `create`/`edit` pages: you give it a `resource` name and (for edits) an `entry`, and it figures out the rest.

The default rendering:

- Resolves the route name `dashboard.{resource}.store` (create) or `dashboard.{resource}.update` (edit).
- Builds the form `action` and sets `method` to `POST` (create) or `PUT` (edit).
- Sets the card title and submit label to localized **Create** / **Update**.
- Includes the partial `dashboard.{resource}.partials.form` as the card body (when no slot is provided), passing `entry` to it.
- Renders a Back link to `dashboard.{resource}.index` unless disabled.

## Props

All props are the constructor arguments of `App\View\Components\FormCard`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `resource` | `string` | required | Resource key used to derive route names (`dashboard.{resource}.store|update|index`) and the form partial path (`dashboard.{resource}.partials.form`). |
| `entry` | `mixed` | `null` | The existing model being edited. When present, the component switches to "edit" mode (update route, `PUT`, "Update" label) and appends the entry to the route params. |
| `data` | `array` | `[]` | Extra data merged into the view and passed to the included form partial (e.g. select options). `entry` is always merged in. |
| `title` | `?string` | `null` → `__('Edit')` if `entry`, else `__('Create')` | Card header title. |
| `submit` | `?string` | `null` → `__('Update')` if `entry`, else `__('Create')` | Submit button label. |
| `action` | `?string` | `null` (auto-built) | Explicit form action URL. If set, route resolution is skipped. |
| `method` | `?string` | `null` → `'PUT'` if `entry`, else `'POST'` | HTTP method. The underlying `<x-form>` spoofs `PUT/PATCH/DELETE`. |
| `route` | `?string` | `null` → `dashboard.{resource}.store|update` | Route name used to build the action (ignored if `action` is set). |
| `routeParams` | `array` | `[]` | Parameters for the route. For edits the `entry` is automatically appended. |
| `back` | `string\|null\|false` | `null` → `dashboard.{resource}.index` | Back link target. Pass `false` to hide the Back button entirely. |
| `backParams` | `array` | `[]` | Parameters for the back route. |

> The card root element gets the `card` class, and any attributes you pass (via `$attributes->class('card')`) are merged onto it.

### Slots

- **default slot** — If you provide body content, it replaces the auto-included form partial entirely (the `card-body` wrapper is not added, so include your own). If the slot is empty, `dashboard.{resource}.partials.form` is included inside a `card-body` div.
- **`header` slot** — Optional content rendered in the card header next to the title. When present, the header uses `d-flex justify-content-between align-items-center`.

### Footer behavior

The footer always renders the submit button. The Back link renders only when `back` is not `false`, linking to `back_or_route($back, $backParams)` (returns the previous URL or falls back to the named route).

## Usage

### Create page (minimal)

```blade
<x-layouts::dashboard>
    <x-form-card resource="users" />
</x-layouts::dashboard>
```

This resolves the action to `route('dashboard.users.store')`, method `POST`, title/button "Create", body included from `dashboard.users.partials.form`, and a Back link to `dashboard.users.index`.

### Edit page

```blade
<x-layouts::dashboard>
    <x-form-card resource="users" :entry="$user" />
</x-layouts::dashboard>
```

With `entry`, it switches to `route('dashboard.users.update', [$user])`, method `PUT`, and "Update" labels.

### Passing extra data to the form partial

```blade
<x-form-card resource="roles" :data="['permissions' => $permissions]" />
```

```blade
<x-form-card resource="admins" :entry="$admin" :data="['roles' => $roles]" />
```

The `data` array is forwarded to the included partial, so `$permissions` / `$roles` are available there.

### Using the header slot

```blade
<x-layouts::dashboard>
    <x-form-card resource="static-pages" :entry="$staticPage">
        <x-slot:header>
            <x-translatable-switcher />
        </x-slot:header>
    </x-form-card>
</x-layouts::dashboard>
```

### Custom route params and hiding the Back button

```blade
<x-form-card
    resource="languages.tokens"
    :entry="$token"
    :back="false"
    :route-params="[$language]"
/>
```

Here `:back="false"` removes the Back link, and `:route-params="[$language]"` supplies the parent route parameter; the `$token` entry is appended automatically, producing `route('dashboard.languages.tokens.update', [$language, $token])`.

## Gotchas

- The form partial path is convention-based: `resources/views/dashboard/{resource}/partials/form.blade.php` must exist (with dots in `resource` becoming nested directories, e.g. `languages.tokens` → `languages/tokens/partials/form`).
- Providing the default slot bypasses the auto-included partial **and** the `card-body` wrapper — add your own `card-body` if you need it.
- `entry` drives almost all defaults (route, method, title, submit). Override any of them explicitly when the convention does not fit.

## Related

- [Form component](/components/form) — the underlying `<x-form>` that handles CSRF, method spoofing, and the `_form` identifier.
