# Datatable Columns

Columns decide what each cell shows and how it behaves — its label, width, whether it can be searched or sorted, and how the value is formatted. You return them from your datatable's `columns()` method. See the [Datatables overview](/packages/datatables/overview) for the surrounding setup.

## Usage

Build each column with the column type's `make()` (passing the attribute name and a label) and chain options onto it:

```php
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Columns\DateColumn;
use Redot\Datatables\Columns\TernaryColumn;

public function columns(): array
{
    return [
        TextColumn::make('name', __('Name'))->searchable()->sortable(),
        TextColumn::make('email', __('Email'))->email()->searchable(),
        DateColumn::make('created_at', __('Created'))->relative()->sortable(),
        TernaryColumn::make('email_verified_at', __('Verified')),
    ];
}
```

Use a dotted name (e.g. `author.name`) to pull a value from a relationship.

## Options

These apply to every column type:

- **`searchable`** — include the column in the global search box.
- **`sortable`** — let users sort by clicking the header.
- **`sorter`** / **`searcher`** — supply a callback for custom sort/search logic (each also turns the feature on).
- **`width`** — set a column width; accepts optional `min` and `max` as named arguments. `minWidth` / `maxWidth` set those individually.
- **`fixed`** — pin the column to the `start` or `end` while the table scrolls horizontally.
- **`nowrap`** — keep cell text on one line with ellipsis truncation (the default); turn it off to allow wrapping.
- **`empty`** — text shown when the value is null (defaults to `-`).
- **`default`** — value used when the column has no name binding.
- **`getter`** — a callback that transforms the raw value for display, without subclassing.
- **`html`** — render the value as raw HTML instead of escaping it.
- **`visible`** / **`hidden`** — show or hide the column.
- **`exportable`** — include or exclude the column from exports.
- **`class`** / **`css`** / **`attribute`** / **`attributes`** — add CSS classes, inline styles, or HTML attributes to each cell.

Values are escaped by default; reach for `html` (or a type that emits markup) only when you need raw HTML.

## Column types

### TextColumn

The general-purpose text cell. Beyond the shared options it can decorate the value:

- **`email`** / **`phone`** — turn the value into a clickable `mailto:` / `tel:` link.
- **`url`** — link to the value (optionally a new tab or Fancybox).
- **`route`** — link to a named route, with the row available to the parameter callbacks.
- **`prefix`** / **`suffix`** — prepend or append text.
- **`truncate`** / **`wordCount`** — shorten by character or word count.
- **`pad`** — pad the value to a fixed length.

```php
TextColumn::make('title', __('Title'))
    ->route('posts.show', target: '_blank')
    ->searchable();
```

### DateColumn

Formats date/time values, respecting the active locale:

- **`date`** / **`time`** / **`datetime`** — preset formats.
- **`format`** — any PHP date format string.
- **`relative`** — show a human-friendly "2 hours ago" style.

```php
DateColumn::make('published_at', __('Published'))->relative()->sortable();
```

### NumericColumn

- **`precision`** — number of decimal places to format to (left raw by default).

```php
NumericColumn::make('views', __('Views'))->sortable();
```

### StatusColumn

Maps raw status values to readable labels and colored badges:

- **`labels`** — map each value to a display label.
- **`classes`** — map each value to a CSS class for the badge.

```php
StatusColumn::make('status', __('Status'))
    ->labels(['published' => __('Published'), 'draft' => __('Draft')])
    ->classes(['published' => 'bg-success-lt', 'draft' => 'bg-warning-lt']);
```

### TagsColumn

Renders an array of values as chips:

- **`limit`** — how many chips to show before collapsing the rest.
- **`ellipsis`** — the overflow chip's text.

```php
TagsColumn::make('tags', __('Tags'))->limit(5)->searchable();
```

### TernaryColumn

Shows one of two labels for a truthy/falsy value, with success/danger styling. Defaults to localized "Yes" / "No":

- **`true`** / **`false`** — the labels for each state.

```php
TernaryColumn::make('email_verified_at', __('Verified'));
```

### ColorColumn

Renders the value as a color swatch.

```php
ColorColumn::make('color', __('Color'));
```

### IconColumn

Renders the value as an icon class.

```php
IconColumn::make('icon');
```

## Examples

### A computed display column

Use `getter` for inline computed text without a custom type:

```php
TextColumn::make('published', __('State'))
    ->width('150px')
    ->getter(fn ($value) => $value ? __('Published') : __('Draft'));
```

### A flexible-width primary column

```php
TextColumn::make('title', __('Title'))
    ->width('100%', min: '300px')
    ->searchable()
    ->sortable();
```

## Related

- [Add filters](/packages/datatables/filters)
- [Add row & bulk actions](/packages/datatables/actions)
- [Datatables overview](/packages/datatables/overview)
