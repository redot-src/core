# UserAuditable

`Redot\Traits\UserAuditable` automatically records *who* created, updated, and deleted an Eloquent model by stamping the authenticated user's id into `created_by`, `updated_by`, and `deleted_by` columns. It hooks into the model's lifecycle events so you never have to set these fields by hand.

## How it works

When you add the trait to a model, Laravel calls its `bootUserAuditable()` method, which registers three model event listeners:

- **`creating`** — sets `created_by` to the current user id.
- **`updating`** — sets `updated_by` to the current user id, but only if the `updated_by` attribute is present on the model.
- **`deleting`** — sets `deleted_by` to the current user id and persists it via `save()`, but only if the `deleted_by` attribute is present on the model.

In every case the trait only stamps the column when:

1. The field is not already dirty (you can manually override it and your value wins), and
2. A user is authenticated on the resolved guard (`auth($guard)->check()`).

For `updated_by` and `deleted_by` the listener first checks `array_key_exists(...)` against `attributesToArray()`, so if your table/model has no such column the event is a no-op. `created_by`, by contrast, is always set on creation when a user is authenticated.

### The guard

The trait resolves the guard from your auth config:

```php
public static function getUserAuditableGuard()
{
    return config('auth.defaults.guard');
}
```

All stamping uses `auth($guard)->id()`. By default this is your application's default guard. Override `getUserAuditableGuard()` on the model if a given model should be audited against a different guard.

### The related user model

Relationships resolve the user model from the guard's provider:

```php
public static function getUserAuditableProvider()
{
    return config('auth.providers.' . static::getUserAuditableGuard() . '.model');
}
```

## Expected columns

The trait expects up to three nullable foreign-key columns on the model's table. `created_by` is required for creation stamping to be useful; `updated_by` and `deleted_by` are optional — if absent, those events are skipped silently.

```php
$table->foreignId('created_by')->nullable()->constrained('admins');
$table->foreignId('updated_by')->nullable()->constrained('admins');
$table->foreignId('deleted_by')->nullable()->constrained('admins');
```

The `constrained()` target should point at the table backing your guard's user provider model (here, `admins`). `deleted_by` is meaningful only when the model also uses `SoftDeletes` — a hard delete still fires `deleting`, but with no row left to read the value back from.

## Relationships

The trait provides three `belongsTo` relationships, each keyed on the matching column. They return `null` (instead of a relation) when no provider model is configured, so guard against that in code that might run without an auth provider:

```php
public function createdBy() // belongsTo(provider, 'created_by')
public function updatedBy() // belongsTo(provider, 'updated_by')
public function deletedBy() // belongsTo(provider, 'deleted_by')
```

## Usage

Add the trait to any model. From the consumer dashboard's `App\Models\ShortenedUrl`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Redot\Traits\Taggable;
use Redot\Traits\UserAuditable;

class ShortenedUrl extends Model
{
    use SoftDeletes, Taggable, UserAuditable;

    protected $fillable = ['url', 'slug', 'title', 'clicks', 'tags'];
}
```

With the trait in place, the audit columns fill themselves:

```php
// Authenticated as admin #5
$url = ShortenedUrl::create([
    'url'  => 'https://example.com',
    'slug' => 'abc12345',
]);
$url->created_by; // 5

$url->update(['title' => 'Example']);
$url->updated_by; // 5

$url->delete();   // soft delete
$url->deleted_by; // 5
```

Reading the responsible users through the relationships:

```php
$url->load(['createdBy', 'updatedBy', 'deletedBy']);

$url->createdBy?->name;
$url->updatedBy?->name;
$url->deletedBy?->name;
```

You can override a stamp by setting the value yourself before saving — because the trait skips fields that are already dirty:

```php
$url = new ShortenedUrl(['url' => '...', 'slug' => '...']);
$url->created_by = 99; // wins; trait will not overwrite it
$url->save();
```

## Gotchas

- **No user, no stamp.** Outside an authenticated request (console commands, queued jobs, seeders), `auth($guard)->check()` is `false`, so columns stay `null` unless you set them explicitly.
- **`updated_by` / `deleted_by` are opt-in by schema.** Their listeners early-return when the attribute is missing from `attributesToArray()`. Make sure the column exists *and* is loaded on the model instance.
- **`deleted_by` triggers an extra save.** The `deleting` listener calls `$model->save()` to persist the id before the delete completes; combine with `SoftDeletes` so the row survives to hold the value.
- **`created_by` has no column guard.** Unlike the other two, the creating listener does not check for the column's existence — ensure a `created_by` column exists on tables using the trait.
- The relationship methods return `null` when no provider model is configured for the guard; call them defensively (`$model->createdBy()?->...`) in that scenario.

The audit columns pair naturally with [Datatables](/packages/datatables/overview) filters and query-builder schemas (e.g. exposing "Created By" / "Updated By" / "Deleted By" columns mapped to user names).
