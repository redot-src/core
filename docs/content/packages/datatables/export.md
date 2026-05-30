# Datatable Export (PDF)

Every datatable can offer an export menu so users download the current dataset. This page covers PDF export specifically — turning it on, choosing the rendering engine, and supplying your own layout. See the [Datatables overview](/packages/datatables/overview) for the other formats and the table surface in general.

## Enabling PDF export

PDF is configured in `config/datatables.php` under the `export` block. Publish the config first:

```bash
php artisan vendor:publish --tag=datatables::config
```

PDF export needs a rendering library installed. The default uses mPDF — install it and you're done:

```bash
composer require mccarlosen/laravel-mpdf
```

If the export menu offers PDF but the library isn't installed, the export fails with a clear "missing dependency" error. Install the matching package to fix it.

## Options

Configure these under `export.pdf` in `config/datatables.php`:

- **`enabled`** — whether PDF appears among the table's export choices.
- **`template`** — the Blade view rendered into the PDF. Defaults to the packaged template; point it at your own for a custom layout.
- **`adapter`** — which engine renders the PDF. Defaults to mPDF; switch to DomPDF here.
- **`options`** — passed straight to the engine (e.g. page `format` and `orientation`).

## Examples

### Switching to DomPDF

Install the library and point the adapter at it:

```bash
composer require barryvdh/laravel-dompdf
```

```php
// config/datatables.php
'adapter' => \Redot\Datatables\Adapters\PDF\DomPdf::class,
```

### Using your own PDF layout

Set `template` to your own Blade view. Your template receives `$headings` (an array of column labels) and `$rows` (each row as an array of already-rendered cells). Render the cells with `{!! $cell !!}` so column formatting comes through, and keep the `<!-- chunk -->` marker before each row if you stay on mPDF (it uses it to stream large tables):

```blade
{{-- resources/templates/pdf/datatable.blade.php --}}
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
                    <td colspan="{{ count($headings) }}">{{ __('No data available') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-layouts::pdf>
```

```php
// config/datatables.php
'template' => 'templates.pdf.datatable',
```

To tweak the packaged template instead of replacing it, publish the views and edit your copy:

```bash
php artisan vendor:publish --tag=datatables::views
```

### Per-table overrides

Want one table to use a different engine, layout, or page orientation? Override the engine, template, or options on that datatable class and leave the rest to fall back to config — for example a landscape orientation while keeping the configured A4 format. Exclude individual columns from exports with a column's `exportable` option (see [Columns](/packages/datatables/columns#options)).

## Related

- [Add & configure columns](/packages/datatables/columns)
- [Datatables overview](/packages/datatables/overview)
