<?php

use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use League\Uri\Contracts\UriException;

/**
 * Create a redirect response or redirect to a named route.
 */
function back_or_route(string $route, mixed $parameters = [], bool $absolute = true): string
{
    $url = url()->previous();
    $route = route($route, $parameters, $absolute);

    if (! $url || $url === url()->current()) {
        return $route;
    }

    try {
        $previous = Uri::of($url);
    } catch (UriException) {
        return $route;
    }

    $app = Uri::of(config('app.url') ?: request()->root());

    // Uri lowercases the scheme and host and drops default ports, so a plain comparison is enough.
    $sameOrigin = $previous->host() !== null
        && $previous->scheme() === $app->scheme()
        && $previous->host() === $app->host()
        && $previous->port() === $app->port();

    // When the app lives under a sub-path, the previous URL must be inside it.
    $basePath = $app->path();
    $insideApp = $basePath === '/'
        || $previous->path() === $basePath
        || Str::startsWith($previous->path(), $basePath . '/');

    return $sameOrigin && $insideApp ? $url : $route;
}
