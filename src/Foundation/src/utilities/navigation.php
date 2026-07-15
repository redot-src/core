<?php

use Illuminate\Support\Str;

/**
 * Create a redirect response or redirect to a named route.
 */
function back_or_route(string $route, mixed $parameters = [], bool $absolute = true): string
{
    $url = url()->previous();
    $route = route($route, $parameters, $absolute);
    $appUrl = config('app.url') ?: request()->root();

    if (! $url || $url === url()->current()) {
        return $route;
    }

    if (Str::startsWith($url, $appUrl)) {
        return $url;
    }

    return $route;
}
