# Datatable Export (PDF)

Datatables can export their current dataset to PDF through a small, swappable adapter layer. The PDF generation library (mPDF or DomPDF) lives behind an `Adapter` abstraction so you can choose your engine via config without touching datatable code, and the framework fails loudly with a `MissingDependencyException` when the chosen engine is not installed.

This page covers the PDF export path specifically. For the other formats (XLSX, CSV, JSON) and the datatable surface in general, see [Datatables Overview](/packages/datatables/overview).

## How PDF export fits in

`Redot\Datatables\Datatable::toPdf()` produces the response. It does not generate the PDF itself — it instantiates the configured adapter, validates it, gathers the export data, and delegates the actual rendering:

```php
public function toPdf(): StreamedResponse|Response
{
    $pdfAdapter = new $this->pdfAdapter;

    if (! $pdfAdapter instanceof Adapter || ! $pdfAdapter->supported()) {
        throw new Exceptions\MissingDependencyException(sprintf('The PDF adapter "%s" is not supported.', $this->pdfAdapter));
    }

    [$headings, $rows] = $this->getExportData(sanitize: false);

    return $pdfAdapter->download($this->pdfTemplate, $headings, $rows, $this->pdfOptions);
}
```

Note that, unlike `toXlsx()`/`toCsv()`/`toJson()`, the PDF path calls `getExportData(sanitize: false)`. Cell values are passed through to the template raw and rendered with `{!! $cell !!}`, so columns may contain HTML markup.

The three properties that drive this are resolved in the `Datatable` constructor from config (with per-datatable overrides honored because of the `??=` assignment):

```php
$this->pdfTemplate ??= config('datatables.export.pdf.template');
$this->pdfAdapter  ??= config('datatables.export.pdf.adapter');
$this->pdfOptions   = array_merge(config('datatables.export.pdf.options') ?? [], $this->pdfOptions);
```

## The Adapter contract

All PDF engines extend `Redot\Datatables\Adapters\PDF\Adapter`:

```php
namespace Redot\Datatables\Adapters\PDF;

abstract class Adapter
{
    abstract public function download(
        string $template,
        array $headings,
        Collection $rows,
        array $options = []
    ): StreamedResponse|Response;

    public function supported(): bool
    {
        return true;
    }
}
```

- `download()` renders the Blade `$template` (passed `$headings` and `$rows`), and returns it as a streamed `application/pdf` attachment named `export-Y-m-d_H-i-s.pdf`.
- `supported()` reports whether the underlying library is installed. The base implementation returns `true`; concrete adapters override it to check for the vendor class. `Datatable::toPdf()` calls this before rendering and throws if it returns `false`.

## Built-in adapters

### LaravelMpdf (default)

`Redot\Datatables\Adapters\PDF\LaravelMpdf` wraps the `mccarlosen/laravel-mpdf` package.

```php
$pdf = PDF::chunkLoadView('<!-- chunk -->', $template, compact('headings', 'rows'), [], $options);
```

It uses mPDF's `chunkLoadView`, splitting the rendered HTML on the literal `<!-- chunk -->` marker so very large tables are written to the PDF in chunks (lower peak memory). This is why the PDF templates place `<!-- chunk -->` immediately before each `<tr>` in the table body. `$options` is forwarded as the mPDF config array.

`supported()` returns `class_exists(Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::class)`.

### DomPdf

`Redot\Datatables\Adapters\PDF\DomPdf` wraps `barryvdh/laravel-dompdf`.

```php
PDF::setOptions($options);
$pdf = PDF::loadView($template, compact('headings', 'rows'), []);
```

Here `$options` is applied through DomPDF's `setOptions()`. DomPDF has no chunking, so the `<!-- chunk -->` marker in the template is simply an inert HTML comment.

`supported()` returns `class_exists(Barryvdh\DomPDF\Facade\Pdf::class)`.

Both adapters return the same streamed response shape:

```php
return response()->stream(function () use ($pdf) {
    echo $pdf->output();
}, 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => "attachment; filename=\"$filename\"",
]);
```

## Configuration

The `pdf` block lives under `export` in `config/datatables.php`:

```php
'export' => [
    // ... xlsx, csv, json ...

    'pdf' => [
        'enabled'  => true,
        'template' => 'datatables::pdf.default',
        'adapter'  => \Redot\Datatables\Adapters\PDF\LaravelMpdf::class,
        'options'  => [
            'format'      => 'A4',
            'orientation' => 'P',
        ],
    ],
],
```

- `enabled` — whether PDF appears among the datatable's allowed export formats. The constructor builds `allowedExports` from the enabled keys: `array_keys(array_filter(config('datatables.export'), fn ($e) => $e['enabled']))`.
- `template` — the Blade view rendered into the PDF. Defaults to the package view `datatables::pdf.default`.
- `adapter` — FQCN of the `Adapter` subclass to instantiate. Defaults to `LaravelMpdf`.
- `options` — passed verbatim to the adapter (`format`, `orientation`, etc. for mPDF; or DomPDF options).

To switch engines, install `barryvdh/laravel-dompdf` and point the adapter at it:

```php
'adapter' => \Redot\Datatables\Adapters\PDF\DomPdf::class,
```

Publish the config with the `datatables::config` tag before editing:

```bash
php artisan vendor:publish --tag=datatables::config
```

## The PDF view

The default template `datatables::pdf.default` is a full HTML document with print-friendly inline styles. Key behavior:

- Locale-aware direction: `dir="rtl"` when `app()->getLocale()` starts with `ar`, otherwise `ltr`.
- A `@stack('styles')` slot for adding styles, and a `.page-break` helper class (`page-break-before: always`).
- Renders `$headings` as `<th>` cells and each `$row` cell as raw HTML (`{!! $cell !!}`), with the `<!-- chunk -->` marker before each row.
- Falls back to a translated empty message (`datatables::datatable.empty`) when there are no rows.

You can publish and customize the package views, or point `template` at your own Blade view. The only contract a custom template must honor is that it receives `$headings` (array) and `$rows` (Collection of cell arrays).

```bash
php artisan vendor:publish --tag=datatables::views
```

### Real consumer example

The Redot Dashboard app overrides the template in its `config/datatables.php` and supplies its own layout-based view instead of the packaged default:

```php
// config/datatables.php (consumer)
'pdf' => [
    'enabled'  => true,
    'template' => 'templates.pdf.datatable',
    'adapter'  => LaravelMpdf::class,
    'options'  => ['format' => 'A4', 'orientation' => 'P'],
],
```

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

Note that the consumer keeps the `<!-- chunk -->` marker because it still uses the `LaravelMpdf` adapter, which relies on it for chunked rendering.

## Per-datatable overrides

Because the constructor only fills the properties when unset (`??=`), any datatable class can override the engine, template, or options for itself:

```php
use Redot\Datatables\Datatable;
use Redot\Datatables\Adapters\PDF\DomPdf;

class OrdersDatatable extends Datatable
{
    public string $pdfAdapter  = DomPdf::class;
    public string $pdfTemplate = 'reports.orders-pdf';
    public array  $pdfOptions  = ['orientation' => 'L'];

    // ...
}
```

`pdfOptions` is merged on top of the config defaults, so partial overrides are fine — here `L` (landscape) replaces the default `P`, while `format => A4` is inherited.

## MissingDependencyException

`Redot\Datatables\Exceptions\MissingDependencyException` is a plain `Exception` subclass used across the export methods to signal a missing optional package. For PDF specifically, `toPdf()` throws it when the configured adapter is either not an `Adapter` instance or reports `supported() === false`:

```php
throw new Exceptions\MissingDependencyException(
    sprintf('The PDF adapter "%s" is not supported.', $this->pdfAdapter)
);
```

In practice this means: if `pdf.enabled` is `true` but the engine library is not installed, attempting an export raises this exception. Install the matching package to resolve it:

```bash
# Default adapter
composer require mccarlosen/laravel-mpdf

# Or, if using the DomPdf adapter
composer require barryvdh/laravel-dompdf
```

The same exception type is thrown by `toXlsx()` and `toCsv()` when `maatwebsite/excel` is absent.
