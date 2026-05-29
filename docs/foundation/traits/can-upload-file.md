# CanUploadFile

`Redot\Traits\CanUploadFile` is a small trait that adds file upload and deletion helpers to any class (typically a controller). It stores files under the public `uploads/` directory, generates collision-safe filenames, optionally optimizes images, and returns absolute URLs. It pairs with the global `is_image()` and `create_thumbnail()` helpers for image handling.

## Key concepts

- **Public disk, not Laravel Storage.** Files are written directly into `public_path('uploads/...')` using the `File` facade. The returned value is a full URL built with `URL::to()`, not a storage path.
- **Unique filenames.** Each upload becomes `{slugged-original-name}-{8 random chars}.{ext}`, so re-uploading a file with the same name never overwrites an existing one.
- **Array-aware.** Every method accepts either a single value or an array and recurses, returning the same shape it was given.
- **Optional image optimization.** When enabled, images are re-oriented (using EXIF) and optimized in place.

## Public surface

```php
use Redot\Traits\CanUploadFile;

class MyController
{
    use CanUploadFile;
}
```

### uploadFile()

```php
public function uploadFile(
    UploadedFile|array $file,
    string $path = '',
    bool $optimize = false
): string|array
```

- Moves the uploaded file into `public_path('uploads/' . $path)`, creating the directory (mode `0755`, recursive) if needed.
- Builds the stored filename from `Str::slug()` of the original name plus `Str::random(8)` and the original client extension.
- When `$optimize` is `true` **and** the moved file is an image (checked via `is_image()`), it runs `Intervention\Image\Laravel\Facades\Image::decode(...)->orient()->save(...)` and then `Spatie\LaravelImageOptimizer\Facades\ImageOptimizer::optimize(...)` on the file in place.
- Returns the absolute URL (`URL::to('uploads/' . $path . '/' . $filename)`). If given an array of files, returns an array of URLs.

### deleteFile()

```php
public function deleteFile(string|array $path): bool|array
```

Deletes a file by filesystem path. If the path does not already start with `public_path()`, it is resolved relative to `public_path()` first. Returns the result of `File::delete()` (or an array of results for an array input).

### deleteFileFromUrl()

```php
public function deleteFileFromUrl(string|array $url): bool|array
```

Strips the app root (`URL::to('/')`) off the URL to get a relative path, then delegates to `deleteFile()`. Use this when you stored the value returned by `uploadFile()` (which is a URL).

## Related helpers

These live in `src/helpers.php` and are global functions, not part of the trait, but are commonly used alongside it.

### is_image()

```php
function is_image(string $path): bool
```

Returns `true` when `mime_content_type($path)` starts with `image/`. Note it takes a **filesystem path**, not a URL.

### create_thumbnail()

```php
function create_thumbnail(
    string $path,
    int $width = 100,
    int $height = 100,
    int $quality = 85
): ?string
```

Generates a thumbnail next to the original inside a `thumbnails/` subdirectory, named `{filename}-thumb.{ext}`, preserving aspect ratio. Behavior worth knowing:

- Throws `InvalidArgumentException` if the file does not exist, is not an image, has an unreadable size, or is an unsupported type. Supported types: JPEG, PNG, GIF, WEBP. PNG/GIF transparency is preserved.
- If a thumbnail already exists and is newer than (or as new as) the source, it is reused without re-rendering.
- Returns the thumbnail path **relative to `public_path()`** (the public prefix is stripped via `str_replace(public_path(), '', ...)`), or throws on failure.

## Usage

### Single image upload on a model field

From the dashboard's profile controller, where the returned URL is stored directly on the model:

```php
use Redot\Traits\CanUploadFile;

class ProfileController extends Controller
{
    use CanUploadFile;

    public function update(Request $request)
    {
        $request->validate([
            'profile_picture' => ['nullable', 'image', 'max:1024'],
            // ...
        ]);

        $user = $request->user();

        if ($request->hasFile('profile_picture')) {
            $user->profile_picture = $this->uploadFile(
                $request->file('profile_picture'),
                'dashboard/profile_pictures'
            );
        }

        $user->save();
    }
}
```

### Optimization plus thumbnail generation

The dashboard's `UploaderController` uses the `$optimize` flag and then builds a thumbnail with the helpers. Note the use of `is_image(public_path($path))` against a filesystem path and the URL-to-path conversion of the returned value:

```php
$url  = $this->uploadFile($file, $config->directory, $config->optimize);
$path = str_replace(URL::to('/'), '', $url);

$payload = [
    'url'  => $url,
    'path' => $path,
    'size' => filesize(public_path($path)),
];

if ($config->thumbnail && is_image(public_path($path))) {
    try {
        $payload['thumbnail'] = create_thumbnail(public_path($path));
    } catch (\Exception $e) {
        $payload['thumbnail'] = null;
    }
}
```

### Conditional setting value

The `SettingController` uploads only when a file is present for a given key:

```php
$value = match (true) {
    $request->hasFile($key) => $this->uploadFile($request->file($key), 'settings'),
    is_bool($defaults[$key]) => $request->boolean($key),
    default => $request->input($key),
};
```

### Deleting a previously uploaded file

Since `uploadFile()` returns a URL, delete by URL:

```php
$this->deleteFileFromUrl($user->profile_picture);
```

## Gotchas

- **URLs in, URLs out.** `uploadFile()` returns absolute URLs. To delete what you stored, use `deleteFileFromUrl()`, not `deleteFile()`.
- **`is_image()` / `create_thumbnail()` expect filesystem paths**, so wrap stored relative paths with `public_path()` before passing them.
- **Optimization dependencies.** The `$optimize` path requires the `intervention/image` and `spatie/laravel-image-optimizer` packages (and their underlying CLI optimizers) to be installed and available.
- **Directory creation.** `uploadFile()` derives the directory with `dirname(public_path($path))` and ensures it exists; the files always land under `public/uploads/`.
- **Thumbnail caching is mtime-based**, so replacing a source image with a newer one regenerates the thumbnail on the next `create_thumbnail()` call.
