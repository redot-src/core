<?php

namespace Redot\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

trait CanUploadFile
{
    /**
     * Upload file to path.
     */
    public function uploadFile(UploadedFile|array $file, string $path = '', bool $optimize = false): string|array
    {
        if (is_array($file)) return array_map(fn (UploadedFile $item) => $this->uploadFile($item, $path, $optimize), $file);

        $path = trim(str_replace('\\', '/', $path), '/');

        if (in_array('..', explode('/', $path), true)) {
            throw new InvalidArgumentException('The upload path must stay within the uploads directory.');
        }

        $path = 'uploads' . ($path === '' ? '' : '/' . $path);
        File::ensureDirectoryExists(public_path($path), 0755, true);

        $extension = strtolower((string) $file->guessExtension());

        if (! in_array($extension, config('redot.uploads.allowed_extensions', []), true)) {
            throw ValidationException::withMessages([
                'file' => __('Files of type [:type] are not allowed.', ['type' => $extension ?: (string) $file->getMimeType()]),
            ]);
        }

        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = (Str::slug($original) ?: 'upload') . '-' . Str::random(8) . '.' . $extension;

        $fullPath = $path . '/' . $filename;
        $absolutePath = public_path($fullPath);

        $file->move(public_path($path), $filename);

        if ($optimize && is_image($absolutePath)) {
            File::put($absolutePath, Image::fromPath($absolutePath)->orient()->toBytes());
            ImageOptimizer::optimize($absolutePath);
        }

        return URL::to($fullPath);
    }

    /**
     * Delete file from path.
     */
    public function deleteFile(string|array $path): bool|array
    {
        if (is_array($path)) {
            return array_map(fn (string $item) => $this->deleteFile($item), $path);
        }

        $publicPath = realpath(public_path());
        $path = realpath(Str::startsWith($path, public_path()) ? $path : public_path($path));

        // Ensure the file is within the public directory to prevent traversal attacks
        if ($publicPath === false || $path === false || ! Str::startsWith($path, $publicPath . DIRECTORY_SEPARATOR)) {
            return false;
        }

        return File::delete($path);
    }

    /**
     * Delete file from URL.
     */
    public function deleteFileFromUrl(string|array $url): bool|array
    {
        if (is_array($url)) {
            return array_map(fn (string $item) => $this->deleteFileFromUrl($item), $url);
        }

        $baseUrl = rtrim(URL::to('/'), '/');

        if (! Str::startsWith($url, $baseUrl . '/uploads/')) {
            return false;
        }

        $path = rawurldecode((string) parse_url(Str::after($url, $baseUrl), PHP_URL_PATH));

        if (in_array('..', explode('/', str_replace('\\', '/', $path)), true)) {
            return false;
        }

        return $this->deleteFile($path);
    }
}
