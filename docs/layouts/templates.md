# Templates

The dashboard ships a set of reusable Blade *templates* under `resources/templates/`. These are not full pages or layouts you render directly — they are small, named partials consumed by other systems: the **PDF datatable export** renderer and the **Tom Select** remote-select renderer. Each template receives a fixed set of variables from its caller and produces HTML.

## What it is

There are two families of templates:

| Family | Path | Consumed by | Variables passed in |
| --- | --- | --- | --- |
| PDF export | `templates/pdf/datatable.blade.php` | DataTables PDF export | `$headings`, `$rows` |
| Select option | `templates/select/{name}.blade.php` | `<x-select>` remote search | `$item` (an Eloquent model) |

You wire a template into a feature by name (e.g. `template="user"` on `<x-select>`, or `'template' => 'templates.pdf.datatable'` in the datatables config) rather than calling it yourself.

## PDF datatable template

`resources/templates/pdf/datatable.blade.php` is the default markup used when a datatable is exported to PDF. It is registered in `config/datatables.php`:

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

### Variables

| Variable | Type | Description |
| --- | --- | --- |
| `$headings` | `array` | Column header labels. |
| `$rows` | `iterable` | Each entry is an array of pre-rendered cell values. |

### Behavior

- Wraps the table in the `<x-layouts::pdf>` layout (`resources/layouts/pdf.blade.php`), which supplies the PDF page chrome.
- Renders one `<th>` per heading and one `<td>` per cell. Cells are emitted with `{!! $cell !!}` (unescaped), so cell content may contain HTML.
- Includes an HTML comment marker `<!-- chunk -->` before each row; the exporter uses this to chunk large tables during generation.
- When there are no rows it renders a single full-width cell spanning `count($headings)` columns with the translated string `No data available`.

```blade
<x-layouts::pdf>
    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($rows as $row)
                <!-- chunk -->
                <tr>
                    @foreach ($row as $cell)
                        <td>{!! $cell !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">
                        {{ __('No data available') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-layouts::pdf>
```

To customize the PDF output, copy this file and point the `template` key in `config/datatables.php` at your own view. See [DataTables export](/packages/datatables/export) for the full export pipeline.

## Select option templates

The select templates render the rich option markup for a remote `<x-select>` that fetches results from the server. The `<x-select>` component (`app/View/Components/Select.php`) accepts a `template` prop; the select controller renders that view once per result row.

### How a template is resolved

In `Select::render()` the prop is prefixed automatically:

```php
// If template doesn't start with `templates.select`, we will prefix it.
if ($this->template && ! str_starts_with($this->template, 'templates.select')) {
    $this->template = 'templates.select.' . $this->template;
}
```

So `template="user"` resolves to `templates.select.user`. If you pass a fully-qualified path it is used as-is. A non-existent template throws `InvalidArgumentException` when the query data is built.

### How a template is rendered

The select controller (`app/Http/Controllers/SelectController.php`) renders the template per matched model and ships the HTML to Tom Select as the option's `__html`:

```php
'__html' => $data->template ? view($data->template, ['item' => $item])->render() : '',
```

### Variables

| Variable | Type | Description |
| --- | --- | --- |
| `$item` | Eloquent model | The matched record. Relations referenced in `search` / `appends` are eager-loaded before rendering. |

Only fields/relations that are part of the select's `key`, `text`, `search`, or `appends` columns are guaranteed to be loaded, so templates must reference accessors/attributes that are available on the eager-loaded model.

### Built-in templates

**`templates/select/admin.blade.php`** — avatar + name + email, using the model's `profile_picture`:

```blade
<div class="d-flex align-items-start gap-3">
    <x-avatar class="flex-shrink-0" :name="$item->name" :image="$item->profile_picture" />

    <div>
        <div class="fw-medium">{{ $item->name }}</div>
        <div class="text-secondary">{{ $item->email }}</div>
    </div>
</div>
```

**`templates/select/user.blade.php`** — avatar (initials only) + `full_name` + email:

```blade
<div class="d-flex align-items-start gap-3">
    <x-avatar class="flex-shrink-0" :name="$item->full_name" />

    <div>
        <div class="fw-medium">{{ $item->full_name }}</div>
        <div class="text-secondary">{{ $item->email }}</div>
    </div>
</div>
```

**`templates/select/country.blade.php`** — flag icon (CSS class built from `$item->code`) + country name:

```blade
<div class="d-flex align-items-center gap-2">
    <span class="flag flag-xs flag-country-{{ $item->code }}"></span>
    <span>{{ $item->name }}</span>
</div>
```

## Usage

These select examples are taken straight from the dashboard.

Impersonate-admins screen — uses the `admin` template:

```blade
<x-select name="admin_id" :title="__('Admin')" :query="$admins" template="admin" validation="required" />
```

Impersonate-users screen — uses the `user` template, with the searched columns declared so they are loaded for the template:

```blade
<x-select name="user_id" :title="__('User')" :query="$users" text="full_name"
    search="full_name, email" template="user" validation="required" />
```

Countries component (`resources/components/countries.blade.php`) — uses the `country` template with a custom `key`:

```blade
<x-select :query="\App\Models\Country::class" key="code" template="country" same-template {{ $attributes }} />
```

## Gotchas

- **Select templates only apply to remote selects.** They are rendered server-side per result; a plain (non-`query`) select never uses them.
- **Reference only loaded data.** Make sure any attribute or relation your template uses is covered by the select's `key`, `text`, `search`, or `appends` so it is eager-loaded.
- **PDF cells are unescaped.** `{!! $cell !!}` trusts the exporter's cell formatting; sanitize upstream if cells include user input.
- **Naming.** A select template name is auto-prefixed with `templates.select.`; you only pass the short name (`user`, `admin`, `country`).

## Related

- [Select component](/components/select)
- [DataTables export](/packages/datatables/export)
