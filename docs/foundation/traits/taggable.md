# Taggable

`Redot\Traits\Taggable` adds lightweight, free-form tagging to an Eloquent model. Tags are stored as a JSON array on a single column of the model itself — there are no pivot tables, tag models, or extra relationships involved. It exists for cases where you just want a flat list of string tags per row plus a few convenience helpers to query and mutate them.

## Key concepts

- Tags live on **one column of the model**, cast to `array`. By default that column is named `tags`.
- The column name is configurable per model via a protected static property:

  ```php
  protected static string $tagsAttribute = 'tags';
  ```

- Because tags are stored as JSON on the row, querying by tag uses `whereJsonContains`, and aggregating all tags reads them straight off the table.

## Setup

The model must have a column matching `$tagsAttribute` (default `tags`), and that column should be cast to `array` and be mass-assignable if you want to create/update it through `fill`.

Migration column:

```php
$table->json('tags')->nullable();
```

Model wiring (from the consumer app's `App\Models\ShortenedUrl`):

```php
use Redot\Traits\Taggable;

class ShortenedUrl extends Model
{
    use Taggable;

    protected $fillable = [
        'url', 'slug', 'title', 'clicks', 'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];
}
```

To store tags under a different column, override the property:

```php
class Post extends Model
{
    use Taggable;

    protected static string $tagsAttribute = 'labels';
}
```

## Public surface

### `scopeTagged($query, array|string|null $tags)`

A query scope that filters rows that contain **any** of the given tags (OR semantics). Accepts a single tag string, an array of tags, or `null`. Passing `null` is a no-op and returns the query unchanged.

```php
// Single tag
ShortenedUrl::tagged('marketing')->get();

// Any of several tags
ShortenedUrl::tagged(['marketing', 'launch'])->get();

// null is ignored — handy for optional filters
ShortenedUrl::tagged(request('tag'))->get();
```

Internally it wraps the conditions in a nested `where` and uses `orWhereJsonContains` for each tag.

### `static tags(): array`

Returns every distinct tag used across all rows of the model, as an associative array where the key and value are both the tag string (`['marketing' => 'marketing', ...]`). It pulls the tag column from all rows where it is not null, flattens, and de-duplicates. The value/key shape makes it drop straight into a select's options list.

```php
$tags = ShortenedUrl::tags();
// ['marketing' => 'marketing', 'launch' => 'launch']
```

### `attachTag(string ...$tags): void`

Adds one or more tags to the model's current tags, de-duplicates, and **saves immediately**.

```php
$url->attachTag('marketing');
$url->attachTag('marketing', 'q2', 'launch');
```

### `detachTag(string ...$tags): void`

Removes one or more tags from the model's current tags and **saves immediately**.

```php
$url->detachTag('launch');
```

### `syncTags(?array $tags): void`

Replaces the entire tag set with the given array (or `null` to clear) and **saves immediately**. `attachTag` and `detachTag` are built on top of this.

```php
$url->syncTags(['marketing', 'q2']);
$url->syncTags(null); // clears all tags
```

## Usage

### Populating a tag select from existing tags

`tags()` is designed to feed a select component. The consumer's `ShortenedUrlController` passes it to the create/edit views:

```php
public function create()
{
    return view('dashboard.shortened-urls.create', [
        'tags' => ShortenedUrl::tags(),
    ]);
}
```

The view renders it with a multi-select that also allows entering new tags:

```blade
<x-select
    name="tags[]"
    :title="__('Tags')"
    :options="$tags"
    :value="old('tags', $entry?->tags)"
    tags
    multiple
/>
```

### Saving tags through mass assignment

Because the column is `fillable` and cast to `array`, tags submitted from the form save without calling any trait method — the array goes straight onto the row:

```php
$validated = $request->validate([
    'url' => 'required|url',
    'tags' => 'nullable|array',
]);

ShortenedUrl::create($validated);
```

Use `attachTag` / `detachTag` / `syncTags` when you need to mutate tags imperatively outside of a full fill.

## Gotchas

- **No tag entity.** Tags are plain strings on a JSON column; there is no normalization, no related table, and no referential integrity.
- **`attachTag`, `detachTag`, and `syncTags` save the model.** They each call `save()`, so any other dirty attributes are persisted at the same time, and model events fire.
- **`scopeTagged` is OR, not AND.** A row matches if it contains at least one of the requested tags; there is no built-in "must contain all" variant.
- **The column must be array-cast** (and exist) for these methods to work correctly; without the cast you would read/write a raw JSON string.
- **`tags()` reads the whole table** (selects the tag column from every non-null row). On very large tables consider caching the result.

## Related

- [UserAuditable](/foundation/traits/user-auditable) — another model trait commonly used alongside `Taggable` in the dashboard.
