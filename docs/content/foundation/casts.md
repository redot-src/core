# Casts

The `Union` cast lets a single text column hold a mix of value types — boolean,
integer, array, or string — and read each one back as the type you stored. It is
useful for generic key/value tables (like the settings store) where one `value`
column must hold whatever a given row needs.

## Usage

Apply the cast to the column on your model:

```php
use Illuminate\Database\Eloquent\Model;
use Redot\Casts\Union;

class Setting extends Model
{
    protected function casts(): array
    {
        return ['value' => Union::class];
    }
}
```

Now assign different types to the same column and read them back as stored:

```php
$setting->value = true;          // reads back as bool
$setting->value = 42;            // reads back as int
$setting->value = ['a' => 1];    // reads back as array
$setting->value = 'hello';       // reads back as string
$setting->save();
```

## Behavior

- **Booleans** — round-trip exactly.
- **Integers** — round-trip exactly. There is no float support, so a stored
  `1.5` reads back as `1`.
- **Arrays** — stored as JSON and decoded back to an associative array (never an
  object).
- **Numeric-looking strings lose their type** — a value like `"007"` or `"42"`
  is read back as the integer `7` / `42`. Don't use this cast for columns that
  must preserve numeric strings verbatim.
- **Malformed JSON throws** — invalid stored JSON (or an unencodable array)
  raises an exception on read/write.

## Related

- [Settings](/foundation/settings) — the settings store that uses this cast.
- [Models](/foundation/models) — the models shipped with the package.
