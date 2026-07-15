<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Image;

/**
 * Check if the given path is an image.
 */
function is_image(string $path): bool
{
    return str_starts_with(mime_content_type($path), 'image/');
}

/**
 * Create a thumbnail for the given path inside the same directory under "thumbnails" directory.
 */
function create_thumbnail(string $path, int $width = 100, int $height = 100, int $quality = 85): ?string
{
    if (! file_exists($path)) {
        throw new InvalidArgumentException("File does not exist: $path");
    }

    if (! is_image($path)) {
        throw new InvalidArgumentException("File is not an image: $path");
    }

    $pathInfo = pathinfo($path);
    $directory = $pathInfo['dirname'];
    $filename = $pathInfo['filename'];
    $extension = strtolower($pathInfo['extension']);

    $thumbnailsDir = $directory . DIRECTORY_SEPARATOR . 'thumbnails';
    if (! is_dir($thumbnailsDir)) {
        mkdir($thumbnailsDir, 0755, true);
    }

    $thumbnailPath = $thumbnailsDir . DIRECTORY_SEPARATOR . $filename . '-thumb.' . $extension;

    if (file_exists($thumbnailPath) && filemtime($thumbnailPath) >= filemtime($path)) {
        return str_replace(public_path(), '', $thumbnailPath);
    }

    $thumbnail = Image::fromPath($path)->scale($width, $height)->quality($quality);
    File::put($thumbnailPath, $thumbnail->toBytes());

    return str_replace(public_path(), '', $thumbnailPath);
}
