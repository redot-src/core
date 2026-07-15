<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Redot\Models\Setting;

/**
 * Get the specified setting value.
 */
function setting(?string $key = null, mixed $default = null, bool $fresh = false): mixed
{
    if (is_null($key)) {
        return Setting::all()->pluck('value', 'key')->toArray();
    }

    return Setting::get($key, $default, $fresh);
}

/**
 * Get the application name.
 */
function app_name(): string
{
    return Arr::get(setting('app_name'), app()->getLocale()) ?: config('app.name');
}

/**
 * Get the application url.
 */
function app_url(): string
{
    return URL::to('/');
}
