# UserAuditable

Add this trait to an Eloquent model to automatically record *who* created,
updated, and deleted each row — stamping the authenticated user's id into
`created_by`, `updated_by`, and `deleted_by` columns. You never set these fields
by hand.

## Usage

Add the audit columns to your table:

```php
$table->foreignId('created_by')->nullable()->constrained('admins');
$table->foreignId('updated_by')->nullable()->constrained('admins');
$table->foreignId('deleted_by')->nullable()->constrained('admins');
```

Then use the trait on the model:

```php
use Redot\Traits\UserAuditable;

class ShortenedUrl extends Model
{
    use SoftDeletes, UserAuditable;
}
```

With that in place the columns fill themselves:

```php
// Authenticated as admin #5
$url = ShortenedUrl::create(['url' => 'https://example.com']);
$url->created_by; // 5

$url->update(['title' => 'Example']);
$url->updated_by; // 5

$url->delete();   // soft delete
$url->deleted_by; // 5
```

## What the trait gives you

- **Automatic stamping** — sets `created_by` on create, `updated_by` on update,
  and `deleted_by` on delete, using the current authenticated user. A column is
  only stamped when it exists on the table and a user is authenticated.
- **`createdBy` / `updatedBy` / `deletedBy` relationships** — load the
  responsible users:

  ```php
  $url->load(['createdBy', 'updatedBy', 'deletedBy']);
  $url->createdBy?->name;
  ```

- **Manual override** — set a stamp yourself before saving and the trait won't
  overwrite it:

  ```php
  $url->created_by = 99; // wins
  $url->save();
  ```

## Options

- **Guard** — stamping uses your app's default auth guard. Override
  `getUserAuditableGuard()` on the model to audit against a different guard
  (which also determines the related user model for the relationships).

## Notes

- **No user, no stamp.** Outside an authenticated request (console, queued jobs,
  seeders), the columns stay `null` unless you set them yourself.
- **`updated_by` / `deleted_by` are opt-in by schema** — their stamping is
  skipped silently if the column isn't present. Make sure the column exists and
  is loaded on the instance.
- **`deleted_by` is meaningful with soft deletes** — a hard delete leaves no row
  to read the value back from, so combine it with `SoftDeletes`.

## Related

- [Taggable](/foundation/traits/taggable) — commonly used on the same models.
- [Datatables](/packages/datatables/overview) — "Created By" / "Updated By"
  columns pair naturally with the audit fields.
