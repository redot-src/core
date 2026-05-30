# Pagination

Redot Dashboard ships a Bootstrap-styled pagination view and registers it as the
application-wide default. Any paginator you render with
<span v-pre>`{{ $paginator->links() }}`</span> uses it automatically — there's
no tag to call and nothing to configure.

## Usage

Paginate in a controller, then render the links in the view:

```php
$users = User::query()->paginate(15);

return view('users.index', compact('users'));
```

```blade
@foreach ($users as $user)
    {{-- ...row... --}}
@endforeach

{{ $users->links() }}
```

The links show numbered pages plus previous/next arrows and a "Showing X to Y
of Z results" summary, all localized through the `pagination.*` translation keys.

## Examples

### Live, in-place pagination in a datatable

Plain `->links()` triggers full-page navigation. For live, in-place pagination
inside a datatable, use the datatables package's own pagination view — see the
[Datatable](/packages/datatables/overview) docs.

## Related

- [Datatable](/packages/datatables/overview) — live, `wire:`-driven pagination.
