# Select Remote

`<x-select-remote>` is a [`<x-select>`](/components/select) whose options come from a model query instead of an inline list. Results are searched and loaded over AJAX as the user types, so it suits large tables like users, admins, or countries.

## Usage

```blade
<x-select-remote name="author_id" :title="__('Author')" :query="\App\Models\User::class" validation="required" />
```

`query` is required. Everything else from [`<x-select>`](/components/select) works here too — `name`, `title`, `hint`, `value`, `multiple`, `removable`, `validation`, and the other [common form-field attributes](/components/overview#shared-form-field-conventions). `value` holds the selected key(s); their labels are fetched when the page loads.

## Options

- **`query`** — the data source: a model class string, or an Eloquent / query builder instance (for example, a scoped query built in the controller).
- **`search`** — column(s) matched against the typed term on the server. Accepts an array or CSV. The `key` and `text` columns are always searched.
- **`key`** — column used as each option's submitted value (`id` by default).
- **`text`** — column used as each option's label (`name` by default).
- **`appends`** — extra attributes or accessors to load with each result, for use in a template.
- **`template`** — a short template name used to render each option. See [select option templates](/layouts/templates#select-option-templates).
- **`same-template`** — render the selected item with the same template as the dropdown option.
- **`create`** — a named route opened in a lightbox from a **+** button next to the select, so users can add a missing record without leaving the form.
- **`create-params`** — parameters for the `create` route.

## Examples

### Search across several columns with a template

```blade
<x-select-remote name="user_id" :title="__('User')" :query="$users" text="full_name"
    search="full_name, email" template="user" validation="required" />
```

### Multiple selection from a scoped query

```blade
<x-select-remote name="admins[]" :title="__('Administrators')" :query="$admins" template="admin" multiple validation="required" />
```

### Store a different column than the id

```blade
<x-select-remote name="role" :title="__('Role')" :query="\Spatie\Permission\Models\Role::class" key="name" :value="$admin?->roles()->first()?->name" removable />
```

### Create a missing option inline

Add `create` with the route of the resource's create page:

```blade
<x-select-remote name="role" :title="__('Role')" :query="\Spatie\Permission\Models\Role::class" key="name" create="dashboard.roles.create" />
```

The create page opens in a lightbox with a `from-select` query value. In that resource's `store` action, check for it and send the new record's key back to the select with [`dispatchBrowserEvent`](/foundation/controllers-and-responses#replying-to-a-parent-window) instead of redirecting:

```php
if ($request->has('from-select')) {
    return $this->dispatchBrowserEvent($request->query('from-select'), [
        'value' => $role->name,
    ]);
}
```

The select closes the lightbox and selects the new record.

## Related

- [Select](/components/select) — the underlying component and its static-options mode.
- [Countries](/components/countries) — a ready-made remote select for countries.
- [Templates](/layouts/templates#select-option-templates) — custom option markup.
- [Tom Select init](/frontend/inits/tomselect) — how remote search initializes.
