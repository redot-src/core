# Casts

Custom Eloquent attribute casts shipped with `redot/core`. The package currently provides a single cast, `Redot\Casts\Union`, for columns that store loosely-typed values (booleans, integers, and JSON) as strings in a single text/varchar column.

## Union

`Redot\Casts\Union` implements `Illuminate\Contracts\Database\Eloquent\CastsAttributes`. It lets one database column hold a *union* of value types — `bool`, `int`, `array`, or plain `string` — by serializing everything to a string on write and reconstructing the original type on read.

This is useful for generic key/value or settings tables where a single `value` column must store whatever type a given row needs.

### How `get()` reads a value

When reading from the database (`get(Model $model, string $key, mixed $value, array $attributes)`), the stored string is converted back as follows, in order:

- `'false'` becomes `false`, `'true'` becomes `true`.
- A numeric string (anything passing `is_numeric()`) is cast to `(int)`.
- A string that starts with `{` or `[` is decoded with `json_decode($value, true, 512, JSON_THROW_ON_ERROR)`, producing an associative array.
- Anything else is returned unchanged.

### How `set()` writes a value

When writing to the database (`set(Model $model, string $key, mixed $value, array $attributes)`):

- A `bool` is stored as the string `'true'` or `'false'`.
- An `array` is stored as JSON via `json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)`.
- Anything else (including `int` and `string`) is stored as-is.

### Behavior and gotchas

- **Integers round-trip, floats do not.** Numeric strings are read back with `(int)`, so a stored `1.5` returns `1` and a stored `"100"` returns the integer `100`. There is no float branch.
- **JSON always decodes to arrays.** Because `get()` passes `true` as the associative flag, decoded objects come back as PHP arrays, never `stdClass`.
- **Numeric-looking strings lose their type.** A genuine string like `"007"` or `"42"` is read back as the integer `7` / `42`. Do not use this cast for columns that must preserve numeric strings verbatim.
- **JSON detection is by leading character.** Only values beginning with `{` or `[` are decoded. A JSON scalar string such as `"true"` is handled by the boolean branch, not the JSON branch.
- **Errors throw.** Both encode and decode use `JSON_THROW_ON_ERROR`, so malformed JSON in storage or unencodable arrays raise a `JsonException`.

## Usage

Reference the cast on a model's `$casts` array (or the `casts()` method on newer Laravel versions) using its fully-qualified class name.

```php
use Illuminate\Database\Eloquent\Model;
use Redot\Casts\Union;

class Setting extends Model
{
    protected $casts = [
        'value' => Union::class,
    ];
}
```

With the cast in place, you can assign different types to the same column and read them back as the type you stored:

```php
$setting = new Setting;

$setting->value = true;          // stored as 'true'
$setting->value = 42;            // stored as '42'
$setting->value = ['a' => 1];    // stored as JSON: {"a":1}
$setting->value = 'hello';       // stored as 'hello'

$setting->save();

// On read:
$setting->refresh();
$setting->value; // bool, int, array, or string depending on what was stored
```

Using the `casts()` method form:

```php
use Illuminate\Database\Eloquent\Model;
use Redot\Casts\Union;

class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'value' => Union::class,
        ];
    }
}
```
