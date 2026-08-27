<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Redot\Http\Middleware\RoutePermission;
use Redot\Support\PermissionNameResolver;

/**
 * Get the route from the url.
 */
function route_from_url(string $url): ?string
{
    try {
        $request = Request::create($url);
        $matchedRoute = Route::getRoutes()->match($request);

        return $matchedRoute->getName() ?: null;
    } catch (Throwable) {
        return null;
    }
}

/**
 * Resolve the guard a route authenticates against.
 */
function route_guard(RoutingRoute $route, string $default = 'admins'): string
{
    foreach (Route::gatherRouteMiddleware($route) as $middleware) {
        if (is_string($middleware) && str($middleware)->startsWith(Authenticate::class . ':')) {
            return str($middleware)->after(':')->before(',')->value();
        }
    }

    return $default;
}

/**
 * Check if the gate allows the given permission.
 */
function route_allowed(RoutingRoute|string $route, ?string $guard = null): bool
{
    $registeredRoute = PermissionNameResolver::route($route);

    // Guess the guard from the route's auth middleware when none is given
    $guard ??= $registeredRoute ? route_guard($registeredRoute) : 'admins';

    if (! auth($guard)->check()) {
        return false;
    }

    // Check if the route has the RoutePermission middleware, if not, allow access
    if ($registeredRoute && ! collect(Route::gatherRouteMiddleware($registeredRoute))->contains(RoutePermission::class)) return true;

    // Resolve the permission name for the route, reusing the already-resolved route instance
    $permission = PermissionNameResolver::resolve($registeredRoute ?? $route);
    if (! $permission) return false;

    $user = auth($guard)->user();

    return Gate::forUser($user)->allows($permission);
}

/**
 * Check if the url is allowed.
 */
function url_allowed(string $url, ?string $guard = null): bool
{
    $urlHost = parse_url($url, PHP_URL_HOST);
    $appHost = parse_url(app_url(), PHP_URL_HOST);

    // Early return if the URL has a different host. Relative URLs are internal.
    if (is_string($urlHost) && (! is_string($appHost) || strcasecmp($urlHost, $appHost) !== 0)) {
        return true;
    }

    return route_allowed(route_from_url($url) ?: '', $guard);
}
