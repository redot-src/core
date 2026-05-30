# Datatable Actions

Actions are the per-row buttons in a table's trailing column — view, edit, delete, restore, and any custom button you need. You return them from your datatable's `actions()` method. See the [Datatables overview](/packages/datatables/overview) for the surrounding setup.

## Usage

The common CRUD actions have ready-made builders. Wrap your list in `defaultActionGroup()` to keep the first couple inline and fold the rest into a dropdown:

```php
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Datatable;

public function actions(): array
{
    return Datatable::defaultActionGroup([
        Action::view('posts.show'),
        Action::edit('posts.edit'),
        Action::delete('posts.destroy'),
    ]);
}
```

Each ready-made builder — `view`, `edit`, `delete`, `restore`, `export` — comes with a sensible label, icon, and HTTP method (and delete/restore ask for confirmation). Pass a route name and optional parameters. By default the current row is bound as the route's first parameter, so `{post}` resolves automatically.

For anything else, start from `Action::make(label, icon)` and point it at a destination.

## Options

- **`label`** / **`icon`** — the button's tooltip text and icon class.
- **`route`** — link to a named route; the row is bound as the first parameter unless you opt out.
- **`href`** — link to a raw URL instead of a route (string or a callback receiving the row).
- **`action`** — run a server-side callback instead of navigating (an inline action). It cannot also have a route or href.
- **`parameters`** / **`body`** — route parameters and a request payload; their values may be callbacks that receive the row.
- **`bounded`** — whether to prepend the row to the route parameters (on by default; pass `false` to opt out).
- **`method`** — the HTTP verb for the request (`get`, `post`, `put`, `patch`, `delete`).
- **`visible`** / **`hidden`** — a static flag, typically tied to authorization.
- **`condition`** — a per-row callback deciding whether the action shows for that row.
- **`confirmable`** — require a confirmation prompt before the action runs (with an optional custom message). A confirmable navigation must use a non-GET method.
- **`fancybox`** — open the link in a Fancybox iframe (on by default for `view`).
- **`newTab`** — open in a new tab.
- **`expanded`** — show the label inline next to the icon instead of as a tooltip.

## Examples

### Authorize and restrict per row

Use `visible` for permission checks (pair it with `route_allowed()`) and `condition` for row-dependent logic:

```php
Action::delete('posts.destroy')
    ->visible(route_allowed('posts.destroy'))
    ->condition(fn (Post $post) => ! $post->trashed()),
Action::restore('posts.restore')
    ->visible(route_allowed('posts.restore'))
    ->condition(fn (Post $post) => $post->trashed()),
```

### A custom POST action with a payload and confirmation

```php
Action::make(__('Publish'), 'fas fa-paper-plane')
    ->visible(route_allowed('posts.publish'))
    ->condition(fn (Post $post) => $post->status === 'draft')
    ->route('posts.publish', method: 'post', bounded: false)
    ->body(['post_id' => fn (Post $post) => $post->id])
    ->confirmable(message: __('Are you sure you want to publish this post?')),
```

### Open in a new tab instead of Fancybox

```php
Action::view('categories.show')->fancybox(false)->newTab();
```

### Grouping actions into a dropdown explicitly

When you want full control instead of `defaultActionGroup`, build a group yourself:

```php
use Redot\Datatables\Actions\ActionGroup;

ActionGroup::make(__('More'))->actions([
    Action::edit('posts.edit'),
    Action::delete('posts.destroy'),
]);
```

A group hides itself when none of its actions would show for the row.

## Related

- [Add & configure columns](/packages/datatables/columns)
- [Add filters](/packages/datatables/filters)
- [Datatables overview](/packages/datatables/overview)
