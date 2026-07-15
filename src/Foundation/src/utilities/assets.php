<?php

use Illuminate\Support\Facades\File;

/**
 * Trigger the build of the dependencies.
 */
function trigger_dependencies_build(): void
{
    File::deleteDirectory(dist_path());
}

/**
 * Generate a hashed asset path.
 */
function hashed_asset(string $path, ?bool $secure = null): string
{
    $hash = null;
    $realPath = public_path($path);

    if (file_exists($realPath)) {
        $hash = md5(filemtime($realPath));
    }

    return asset($path, $secure) . ($hash ? '?v=' . $hash : '');
}

/**
 * Get the path to the distribution directory.
 */
function dist_path(?string $suffix = null): string
{
    return public_path('assets/dist' . ($suffix ? '/' . $suffix : ''));
}
