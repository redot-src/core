# CanUploadFile

Add this trait to a controller to get simple file-upload and delete helpers. It
stores uploads under the public `uploads/` directory with collision-safe
filenames, can optimize images on the way in, and returns the public URL you
store on your model. It pairs with the [`is_image()` and
`create_thumbnail()`](/foundation/helpers) helpers for image handling.

## Usage

Add the trait, then upload a file and store the returned URL:

```php
use Redot\Traits\CanUploadFile;

class ProfileController extends Controller
{
    use CanUploadFile;

    public function update(Request $request)
    {
        $request->validate(['avatar' => ['nullable', 'image', 'max:1024']]);

        if ($request->hasFile('avatar')) {
            $user->avatar = $this->uploadFile(
                $request->file('avatar'),
                'avatars',
            );
        }

        $user->save();
    }
}
```

## What the trait gives you

- **`uploadFile`** — move an uploaded file into `uploads/` (under an optional
  sub-path you pass) and return its public URL. Filenames are made unique
  automatically, so re-uploading a same-named file never overwrites. Pass a third
  argument to optimize images in place. Accepts a single file or an array of
  files and returns the matching shape.
- **`deleteFileFromUrl`** — delete a file given the URL `uploadFile()` returned.
  This is what you normally use, since you store URLs.
- **`deleteFile`** — delete a file given its path (relative to the public
  directory). Use when you stored a path rather than a URL.

## Examples

### Optimize, then generate a thumbnail

Optimize on upload, then build a thumbnail with the helpers (which take a
filesystem path, so wrap the stored path with `public_path()`):

```php
$url  = $this->uploadFile($file, $config->directory, $config->optimize);
$path = str_replace(URL::to('/'), '', $url);

if ($config->thumbnail && is_image(public_path($path))) {
    $thumbnail = create_thumbnail(public_path($path));
}
```

### Deleting a stored file

```php
$this->deleteFileFromUrl($user->avatar);
```

## Notes

- **URLs in, URLs out.** `uploadFile()` returns absolute URLs; delete with
  `deleteFileFromUrl()`, not `deleteFile()`.
- **File types are detected from their contents.** Executable and active web
  formats such as PHP, HTML, and SVG are rejected because uploads are served
  from the public directory. Unsupported types throw `InvalidArgumentException`.
  Customize the allowlist through `redot.uploads.allowed_extensions`; only add
  formats that are safe to serve directly from your application's public origin.
- **Deletion stays inside the public directory.** `deleteFileFromUrl()` accepts
  only local URLs under `uploads/`; traversal and external URLs return `false`.
- **Image helpers expect filesystem paths**, so wrap stored relative paths with
  `public_path()` before calling `is_image()` / `create_thumbnail()`.
- **Image optimization** requires the `intervention/image` and
  `spatie/laravel-image-optimizer` packages to be installed.

## Related

- [Helpers](/foundation/helpers) — `is_image()`, `create_thumbnail()`.
