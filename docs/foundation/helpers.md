# Helpers

`redot/core` ships a set of global helper functions for everyday needs:
reading settings, building permission-aware UI, formatting
values, working with assets, and shaping API responses. They are plain global
functions, so you can call them anywhere — controllers, Livewire components, or
Blade — without importing anything.

## Settings

- **`setting('key', $default)`** — read an application setting, falling back to
  the schema default (or your `$default`) when none is stored. Pass `true` as a
  third argument for an uncached read. Call with no key to get the full map. See
  [Settings](/foundation/settings) for the full reference.

  ```blade
  <x-input name="site_title" :value="setting('site_title')" />
  ```

- **`app_name()`** — the application name for the current locale (from the
  translatable `app_name` setting), falling back to `config('app.name')`.

  ```blade
  <a href="{{ url('/') }}">{{ app_name() }}</a>
  ```

- **`app_url()`** — the application base URL. Mostly used to tell whether a URL
  is internal or external.

## URLs & routing

- **`route_allowed('route.name')`** — whether the current admin may access a
  named route. Use it to show/hide permission-gated UI. Unprotected routes are
  always allowed; protected ones defer to the gate. See
  [Datatables](/packages/datatables/overview) for the common use.

  ```php
  Action::edit('posts.edit')->visible(route_allowed('posts.edit'));
  ```

- **`url_allowed($url)`** — the URL counterpart to `route_allowed()`. External
  URLs are always allowed; internal ones resolve to a route name and defer to
  `route_allowed()`. Handy for conditionally rendering links in Blade.

  ```blade
  @if ($create && url_allowed($create))
      <a href="{{ $create }}" class="btn btn-primary">{{ __('Create') }}</a>
  @endif
  ```

- **`route_from_url($url)`** — resolve a URL string to its matched route name (or
  `null` if nothing matches).

- **`back_or_route('route.name', $params)`** — a safe "go back" URL: the previous
  page when it belongs to the app, otherwise the named route. Prevents open
  redirects to external referrers.

  ```blade
  <a href="{{ back_or_route($back, $backParams) }}" class="btn">{{ __('Back') }}</a>
  ```

## Requests & API responses

- **`throw_api_exception($e)`** — turn any caught exception into the standard JSON
  error envelope (mapping common exceptions to the right HTTP status). You rarely
  call this directly — it is wired in as the global JSON exception renderer. See
  [Controllers & API Responses](/foundation/controllers-and-responses).

  ```php
  try {
      // ...
  } catch (Throwable $e) {
      return throw_api_exception($e);
  }
  ```

- **`is_mobile()` / `is_desktop()`** — detect the device from the request's
  user agent. Both return `false` safely when there is no request (e.g. console).

  ```php
  if (is_mobile()) {
      // serve a compact layout
  }
  ```

## Formatting & display

- **`format_phone($phone, $country)`** — normalize a phone number to E.164 form.
  The default region is `EG`. Validate the input first (with the
  [`Phone` rule](/foundation/rules)) — an unparseable string throws.

  ```php
  format_phone('01001234567');        // +201001234567
  format_phone('2025550123', 'US');   // +12025550123
  ```

- **`switch_badge($value, $true, $false)`** — render a green/red yes/no badge for
  a truthy/falsy value. Returns raw HTML, so echo it unescaped.

  ```blade
  {!! switch_badge($post->is_active) !!}
  {!! switch_badge($post->published, __('Published'), __('Draft')) !!}
  ```

- **`no_content()`** — a muted "No content" placeholder for empty rich-text
  fields. Echo it raw.

  ```blade
  {!! $post->body ?: no_content() !!}
  ```

- **`collect_ellipsis($items, $limit, $ellipsis)`** — take the first few items of
  a collection and, when there are more, append a translated "and N more" marker.
  The `:count` placeholder receives the number of hidden items.

  ```php
  collect_ellipsis($tags, 2, ':count more')->implode(', ');
  // tag-a, tag-b, 3 more
  ```

## Files & images

- **`is_image($path)`** — whether a file is an image (by MIME type). Takes a
  filesystem path, not a URL.

  ```php
  if (is_image(public_path($path))) {
      // ...
  }
  ```

- **`create_thumbnail($path, $width, $height, $quality)`** — generate a thumbnail
  next to an image (in a `thumbnails/` subfolder) and return its public-relative
  path. Keeps aspect ratio, preserves PNG/GIF transparency, and reuses an existing
  thumbnail when it is newer than the source. Defaults to 100×100.

  ```php
  $thumb = create_thumbnail(public_path($path));            // 100x100
  $thumb = create_thumbnail(public_path($path), 200, 200, 90);
  ```

- **`parse_csv($csv, $separator, $callback)`** — turn a comma-separated string
  (or array) into a clean, trimmed, re-indexed array with empties removed.

  ```php
  parse_csv('a, b, , c'); // ['a', 'b', 'c']
  ```

## Assets & build

See the [Asset & Init System](/frontend/asset-system) for the bigger picture.

- **`hashed_asset($path)`** — a public asset URL with a cache-busting `?v=`
  suffix derived from the file's modification time.

  ```blade
  <link rel="stylesheet" href="{{ hashed_asset('/assets/css/app.css') }}" />
  ```

- **`dist_path($suffix)`** — the path to the build output directory, optionally
  with a file appended.

  ```php
  dist_path('init.js'); // .../public/assets/dist/init.js
  ```

- **`trigger_dependencies_build()`** — clear the build output so front-end
  dependencies are regenerated on the next request.

## Querying & components

- **`search_model($query, $columns, $term)`** — apply a grouped `LIKE` search
  across columns onto a query. Dotted columns (e.g. `category.name`) search the
  related model. An empty term leaves the query untouched.

  ```php
  search_model(Post::query(), ['title', 'body', 'category.name'], 'laravel');
  ```

- **`component('name', $data)`** — render a Blade component to a string from PHP,
  outside the usual `<x-...>` syntax. Useful inside Datatable column getters.

  ```php
  ->getter(fn ($value, User $user) => component('avatar', [
      'name' => $user->name,
      'image' => $user->avatar,
  ]))
  ```

## Related

- [Settings](/foundation/settings) — the store behind `setting()` and `app_name()`.
- [Controllers & API Responses](/foundation/controllers-and-responses) — where
  `throw_api_exception()` is used.
- [Datatables](/packages/datatables/overview) — heavy consumer of `route_allowed()`
  and `component()`.
