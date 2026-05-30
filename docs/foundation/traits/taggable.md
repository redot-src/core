# Taggable

Add this trait to an Eloquent model for lightweight, free-form tagging. Tags are
stored as a JSON array on a single column of the model itself — no pivot tables,
tag models, or extra relationships. Reach for it when you just want a flat list
of string tags per row, plus a few helpers to query and mutate them.

## Usage

Add a JSON `tags` column, cast it to `array`, make it fillable, and use the
trait:

```php
use Redot\Traits\Taggable;

class Post extends Model
{
    use Taggable;

    protected $fillable = ['title', 'body', 'tags'];
    protected $casts = ['tags' => 'array'];
}
```

To store tags under a different column, set `protected static string
$tagsAttribute = 'labels';` on the model.

## What the trait gives you

- **`tagged` scope** — filter rows that contain **any** of the given tags (OR
  semantics). Accepts a single tag, an array, or `null` (a no-op, handy for
  optional filters).
- **`tags()`** — every distinct tag used across all rows, returned as a
  `value => value` map that drops straight into a select's options.
- **`attachTag`** — add one or more tags to a row and save immediately.
- **`detachTag`** — remove one or more tags from a row and save immediately.
- **`syncTags`** — replace the entire tag set (or pass `null` to clear) and save
  immediately.

## Examples

### Querying by tag

```php
Post::tagged('laravel')->get();
Post::tagged(['laravel', 'php'])->get();
Post::tagged(request('tag'))->get(); // null is ignored
```

### Feeding a tag select from existing tags

```php
return view('posts.create', [
    'tags' => Post::tags(),
]);
```

```blade
<x-select name="tags[]" :title="__('Tags')" :options="$tags"
    :value="old('tags', $post?->tags)" tags multiple />
```

### Saving tags through mass assignment

Because the column is fillable and array-cast, submitted tags save without any
trait method:

```php
Post::create($request->validate([
    'title' => 'required|string',
    'tags'  => 'nullable|array',
]));
```

Use the mutator helpers when you need to change tags imperatively:

```php
$post->attachTag('laravel', 'php');
$post->detachTag('draft');
$post->syncTags(['laravel', 'php']);
```

## Notes

- **No tag entity.** Tags are plain strings — no normalization, related table, or
  referential integrity.
- **The mutators save the model.** `attachTag`/`detachTag`/`syncTags` each call
  `save()`, so other dirty attributes persist and model events fire.
- **`tagged` is OR, not AND** — a row matches if it has *any* requested tag.
- **`tags()` reads the whole table** — consider caching on very large tables.

## Related

- [UserAuditable](/foundation/traits/user-auditable) — commonly used on the same
  models.
