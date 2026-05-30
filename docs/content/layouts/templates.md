# Templates

Redot Core ships a set of reusable Blade *templates* — small named partials
you wire into a feature by name rather than render directly. There are two
families: the **PDF datatable export** markup and the **remote select** option
markup.

## PDF datatable template

When a datatable is exported to PDF it renders through a template that you point
to from the datatables config:

```php
'pdf' => [
    'enabled' => true,
    'template' => 'templates.pdf.datatable',
    'adapter' => LaravelMpdf::class,
    'options' => [
        'format' => 'A4',
        'orientation' => 'P',
    ],
],
```

The default template wraps the table in the [`<x-layouts::pdf>`](/layouts/overview)
layout and renders a heading row plus one row per record. Cell values are emitted
unescaped, so formatted/HTML cells render as-is. To customize the output, copy
the template to your own view and point the `template` key at it. See
[DataTables export](/packages/datatables/export) for the full pipeline.

## Select option templates

Remote `<x-select>` components — the ones that fetch results from the server —
render each option with a template, so you can show avatars, secondary text, or
flags instead of plain labels. You opt in with the `template` attribute:

```blade
<x-select name="admin_id" :title="__('Admin')" :query="$admins" template="admin" validation="required" />
```

You pass the short template name (`admin`, `user`, `country`, …); it is resolved
under the select templates automatically. The matched record is available in the
template as `$item`.

### Built-in templates

- **`admin`** — avatar + name + email.
- **`user`** — avatar (initials) + name + email.
- **`country`** — a flag icon + country name.

### Make sure your fields are loaded

A template can only use data the select loaded. Declare any attribute or
relation your template references in the select's `key`, `text`, `search`, or
`appends` so it is eager-loaded:

```blade
<x-select name="user_id" :title="__('User')" :query="$users" text="name"
    search="name, email" template="user" validation="required" />
```

### Writing your own

Add a template that renders the option markup using `$item`:

```blade
{{-- a custom country option --}}
<div class="d-flex align-items-center gap-2">
    <span class="flag flag-xs flag-country-{{ $item->code }}"></span>
    <span>{{ $item->name }}</span>
</div>
```

Then reference it by its short name:

```blade
<x-select :query="\App\Models\Country::class" key="code" template="country" same-template {{ $attributes }} />
```

## Gotchas

- **Select templates only apply to remote selects.** A plain (non-`query`)
  select never uses them.
- **Reference only loaded data.** Cover any attribute or relation your template
  uses with the select's `key`, `text`, `search`, or `appends`.
- **PDF cells are unescaped.** Sanitize upstream if cells can contain user input.
- **Use the short name.** Pass `user`, `admin`, or `country` — not a full view
  path.

## Related

- [Select component](/components/select)
- [DataTables export](/packages/datatables/export)
- [Layouts](/layouts/overview)
