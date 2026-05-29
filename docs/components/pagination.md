# Pagination

The pagination view is the dashboard's Bootstrap-styled markup for Laravel's paginator. It is registered as the application-wide **default** paginator view by `redot/core`, so any `LengthAwarePaginator` rendered with <span v-pre>`{{ $paginator->links() }}`</span> automatically uses it — there is no `<x-pagination>` tag to call.

## What it is

The file lives at `resources/components/pagination.blade.php`. Despite sitting in the `components/` directory, it is **not** an anonymous Blade component you invoke as `<x-pagination>`. It is a standard Laravel pagination template, and `redot/core` wires it up as the global default in its service provider:

```php
// vendor/redot/core — RedotServiceProvider::configurePaginatorView()
protected function configurePaginatorView(): void
{
    Paginator::defaultView('components.pagination');
}
```

Because of this, calling `->links()` on any paginator instance renders this template — you never reference it by name.

There is no `app/View/Components/Pagination.php` class. The view receives the standard variables Laravel injects into every pagination view (see below).

## Variables

These are provided by Laravel's paginator, not by a component class. The template relies on:

| Variable | Source | Description |
| --- | --- | --- |
| `$paginator` | The `LengthAwarePaginator` instance | Drives every link and counter via its methods. |
| `$elements` | Computed by Laravel | Array of page elements — each entry is either a `string` (the `…` separator) or an `array` of `page => url` pairs. |

The methods consumed from `$paginator`:

- `onFirstPage()` — disables the previous link.
- `previousPageUrl()` / `nextPageUrl()` — link targets.
- `hasMorePages()` — controls the next link.
- `currentPage()` — marks the active page.
- `firstItem()`, `lastItem()`, `total()` — feed the "Showing X to Y of Z results" summary (each falls back to `0`).

## Markup and styling

The output is a Bootstrap `<nav>` with two layouts:

- A simplified Previous/Next `.pagination` for small screens (`d-sm-none`).
- A full layout for `sm` and up (`d-none flex-sm-fill d-sm-flex …`) showing a results summary plus numbered page links with `&lsaquo;` / `&rsaquo;` arrows.

Page items use Bootstrap classes: `.page-item`, `.page-item.active`, `.page-item.disabled`, and `.page-link`.

## Translations

All visible text is localized through `@lang('pagination.*')` keys, resolved from `lang/{locale}/pagination.php`:

```php
'previous' => '&laquo; Previous',
'next' => 'Next &raquo;',
'showing' => 'Showing',
'to' => 'to',
'of' => 'of',
'results' => 'results',
```

The full layout additionally renders the summary line:

```
Showing <firstItem> to <lastItem> of <total> results
```

## Usage

Paginate in a controller and render `->links()` in the view. No view name is needed — the default is already set to this template.

```php
// Controller
$users = User::query()->paginate(15);

return view('users.index', compact('users'));
```

```blade
{{-- View --}}
@foreach ($users as $user)
    {{-- ...row... --}}
@endforeach

{{ $users->links() }}
```

You can still pass an explicit view name if you ever need to override per call:

```blade
{{ $users->links('components.pagination') }}
```

## Gotchas

- **Not a component.** Do not write `<x-pagination>`; it will not resolve. Use `$paginator->links()`.
- **No Livewire `wire:` bindings.** Links are plain anchor `href`s pointing at `?page=` URLs (full page navigation). For live, in-place pagination inside a datatable, the datatables package ships its own `wire:`-driven pagination view (`datatables::pagination.default`) — see the [Datatable component](/packages/datatables/overview) docs.
- The summary counters are guarded with `?: 0`, so an empty result set renders "Showing 0 to 0 of 0 results" rather than blanks.
