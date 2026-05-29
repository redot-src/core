# Datatable Columns

Columns describe how each piece of data is rendered in a [datatable](/packages/datatables/overview) row. Every column extends the base `Redot\Datatables\Columns\Column`, which handles value resolution, sizing, sorting, searching, and HTML escaping. Specialized subclasses (text, date, numeric, status, etc.) add type-specific formatting on top of that base.

## Key Concepts

- **Fluent construction.** Columns are created with the static `make(?string $name = null, ?string $label = null)` factory and configured by chaining methods, each of which returns `static`.
- **Dotted names = relationships.** When the column `name` contains a dot (e.g. `author.name`), `relationship` is automatically set to `true` and the value is resolved with `data_get()`.
- **Value resolution pipeline.** `get(Model $row)` reads `data_get($row, $name)` (or `default` when there is no name), runs the optional `getter` closure, then the column's type-specific `defaultGetter()`. A `null` result falls back to the `empty` value, and the final string is escaped with `e()` unless `html(true)` is set.
- **Macroable.** The base `Column` uses Laravel's `Macroable` trait, so you can register custom fluent methods at runtime.

## The Base Column

`Redot\Datatables\Columns\Column` exposes the shared configuration surface inherited by every column type.

```php
Column::make('status', 'Status')
    ->label('Status')               // header label
    ->empty('—')                    // shown when the value is null (string or Closure)
    ->default('n/a')                // value used when there is no name binding
    ->width('200px', min: '120px', max: '300px')
    ->maxWidth('300px')
    ->minWidth('120px')
    ->fixed(true, 'start')          // pin to 'start' or 'end'
    ->nowrap(false)                 // allow wrapping (default true: ellipsis truncation)
    ->html(true)                    // render raw HTML instead of escaping
    ->sortable()                    // enable column sorting
    ->sorter(fn ($query, $direction) => ...) // custom sort, also sets sortable
    ->searchable()                  // enable searching
    ->searcher(fn ($query, $term) => ...)    // custom search, also sets searchable
    ->visible()                     // show the column (default)
    ->hidden()                      // inverse of visible
    ->exportable(false)             // include/exclude from exports
    ->getter(fn ($value, $row) => ...)       // transform the raw value
    ->class('text-end')             // append CSS class(es)
    ->css('color: red')             // append inline style(s)
    ->attribute('data-id', fn ($row) => $row->id) // single cell attribute
    ->attributes(['role' => 'cell']);             // multiple cell attributes
```

Defaults worth knowing: `width` is `fit-content`, `empty` is `'-'`, `nowrap` is `true`, `sortable`/`searchable` are `false`, `visible`/`exportable` are `true`, and `fixedDirection` is `'start'`.

The `getter`, `empty`, `sorter`, `searcher`, and any closure attribute values are evaluated through the column's `evaluate()` helper. A plain string is returned as-is; a callable is invoked with the contextual arguments (typically the value and the model row).

## Column Types

### TextColumn

`Redot\Datatables\Columns\TextColumn` formats string values and can render emails, phone numbers, and links.

```php
TextColumn::make('email', 'Email')
    ->prefix('• ')                  // prepend text
    ->suffix(' (verified)')         // append text
    ->email()                       // wrap in <a href="mailto:...">, enables html
    ->phone()                       // wrap in <a href="tel:...">, enables html
    ->url(true, text: 'Open', fancybox: false, target: '_blank') // link to the value
    ->route('posts.show', ['id' => fn ($v, $row) => $row->id], text: 'View', target: '_blank')
    ->truncate(50)                  // Str::limit by character count
    ->wordCount(10)                 // Str::words by word count
    ->pad(8, '0', STR_PAD_LEFT);    // str_pad the value
```

Notes:
- `email()`, `phone()`, `url()`, and `route()` automatically flip `html` to `true`.
- `route()` builds the URL with the model prepended to the parameters: `route($name, array_merge([$row], $parameters))`. Each parameter value is evaluated, so closures receive `($value, $row)`.
- The url/route `text` may be a string or a `Closure` evaluated with `($value, $row)`; it falls back to the value when null.

### DateColumn

`Redot\Datatables\Columns\DateColumn` parses values into `Carbon` (when not already) and formats them.

```php
DateColumn::make('created_at', 'Created')
    ->format('Y-m-d H:i')   // any PHP date format
    ->datetime()            // Y-m-d H:i:s (default)
    ->date()                // Y-m-d
    ->time()                // H:i:s
    ->relative();           // diffForHumans()
```

Non-relative formats use `Carbon::translatedFormat()`, so output respects the active locale. The format constants are `DATETIME_FORMAT`, `DATE_FORMAT`, `TIME_FORMAT`, and `RELATIVE_FORMAT`.

### NumericColumn

`Redot\Datatables\Columns\NumericColumn` optionally formats numbers with `number_format()`.

```php
NumericColumn::make('clicks', 'Clicks')
    ->precision(2)   // number_format($value, 2)
    ->sortable();
```

When `precision` is `null` (the default) the raw value is returned unchanged.

### ColorColumn

`Redot\Datatables\Columns\ColorColumn` renders the value as a color swatch. The cell's `background-color` is set to the resolved value, with transparent text and `user-select: none`. Default `width` is `50px` and it is not exportable.

```php
ColorColumn::make('color', 'Color');
```

### IconColumn

`Redot\Datatables\Columns\IconColumn` renders the value as an icon class inside `<i class="..."></i>`. It is HTML, centered (`text-center`), `50px` wide, and not exportable.

```php
IconColumn::make('icon');
```

### StatusColumn

`Redot\Datatables\Columns\StatusColumn` maps raw status values to human labels and CSS classes. The label map drives display text; the class map appends a CSS class to the matching cell. Centered by default.

```php
StatusColumn::make('status', 'Status')
    ->labels([
        'active'  => __('Active'),
        'pending' => __('Pending'),
    ])
    ->classes([
        'active'  => 'bg-success-lt',
        'pending' => 'bg-warning-lt',
    ]);
```

Unmapped values fall through to the raw value, and unmapped classes simply add nothing.

### TagsColumn

`Redot\Datatables\Columns\TagsColumn` renders an array of values as `<span class="tag">` chips wrapped in `<div class="tags-list">`. It is HTML and shows up to `limit` tags (default `3`), appending an `ellipsis` (default `...`) chip when there are more.

```php
TagsColumn::make('tags', 'Tags')
    ->limit(5)
    ->ellipsis('…')
    ->searchable();
```

Empty or falsy values render the column's `empty` fallback.

### TernaryColumn

`Redot\Datatables\Columns\TernaryColumn` displays one of two strings based on truthiness, with success/danger background classes. Centered, `50px` wide. The `true`/`false` strings default to the `datatables::datatable.yes` / `datatables::datatable.no` translations (`Yes` / `No`).

```php
TernaryColumn::make('email_verified_at', __('Verified'))
    ->true(__('Yes'))
    ->false(__('No'));
```

The cell gets `bg-success-lt` when the value is truthy and `bg-danger-lt` otherwise.

## Usage

Columns are returned from the `columns()` method of a datatable component. These are real definitions from the consumer dashboard.

```php
// app/Livewire/Datatables/Users.php
public function columns(): array
{
    return [
        TextColumn::make('full_name', __('Name'))
            ->width('100%')
            ->minWidth('300px')
            ->searchable()
            ->sortable(),
        TextColumn::make('email', __('Email'))
            ->width('300px')
            ->email()
            ->searchable(),
        TernaryColumn::make('email_verified_at', __('Verified')),
    ];
}
```

```php
// app/Livewire/Datatables/ShortenedUrls.php
public function columns(): array
{
    return [
        TextColumn::make('title', __('Title'))
            ->width('100%', min: '300px')
            ->searchable()
            ->sortable(),
        TextColumn::make('slug', __('Shortened Url'))
            ->route('website.shortened-urls.show', target: '_blank')
            ->searchable(),
        NumericColumn::make('clicks', __('Clicks'))
            ->sortable(),
        TagsColumn::make('tags', __('Tags'))
            ->searchable(),
    ];
}
```

```php
// app/Livewire/Datatables/Memos.php
public function columns(): array
{
    return [
        IconColumn::make('icon'),
        TextColumn::make('title', __('Title'))
            ->width('100%', min: '300px')
            ->searchable()
            ->sortable(),
        DateColumn::make('date', __('Date'))
            ->width('200px')
            ->relative()
            ->sortable(),
    ];
}
```

A `getter` is handy for inline computed display without subclassing:

```php
// app/Livewire/Datatables/Languages.php
TextColumn::make('is_rtl', __('Direction'))
    ->width('150px')
    ->getter(fn ($value) => $value ? __('Right to Left') : __('Left to Right'));
```

## Gotchas

- Output is escaped with `e()` by default. Set `html(true)` (or use a type that does it for you, like `IconColumn`, `TagsColumn`, or `TextColumn::email()`/`url()`) when you need raw markup.
- `sorter()` and `searcher()` implicitly enable `sortable`/`searchable`; you do not need to call both.
- `width()` accepts optional `min`/`max` named arguments; when `minWidth`/`maxWidth` are not set they fall back to `width` in the generated CSS.
- `prepareAttributes()` runs on a clone during rendering, so color/status/ternary classes are computed per row without mutating the shared column instance.
- `ColorColumn`, `IconColumn`, and `TernaryColumn` are not exportable by default (`exportable = false` on color/icon).

See also: [Datatables Overview](/packages/datatables/overview).
