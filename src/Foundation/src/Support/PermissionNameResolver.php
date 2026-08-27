<?php

namespace Redot\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class PermissionNameResolver
{
    public const ACTION_KEY = 'redot.permission';

    public const RESOURCE_ACTIONS_KEY = 'metadata.redot.permissions';

    public const API_PREFIX = 'api.';

    /**
     * Resolve the permission name used by the given route.
     */
    public static function resolve(Route|string $route): ?string
    {
        // A route name that matches a registered route: resolve against the route itself.
        if (is_string($route) && $registeredRoute = static::route($route)) {
            return static::resolve($registeredRoute);
        }

        // A route name with no registered route: fall back to name conventions only.
        if (is_string($route)) {
            return static::resolveName($route);
        }

        // The route declares its permission explicitly via ->permission().
        if ($permission = $route->getAction(static::ACTION_KEY)) {
            return $permission;
        }

        // A resource route with a per-action permission map declared on the resource.
        $permissions = $route->getAction(static::RESOURCE_ACTIONS_KEY);

        if (is_array($permissions) && $permission = $permissions[$route->getActionMethod()] ?? null) {
            return $permission;
        }

        // An api.dashboard.* route: inherit whatever the mirrored dashboard.* route resolves to.
        if ($dashboardRoute = static::dashboardRoute($route->getName())) {
            return static::resolve($dashboardRoute);
        }

        // Nothing declared: derive the permission from the route name.
        return static::resolveName($route->getName());
    }

    /**
     * Find the registered route represented by the given value.
     */
    public static function route(Route|string $route): ?Route
    {
        if ($route instanceof Route) {
            return $route;
        }

        return RouteFacade::getRoutes()->getByName($route);
    }

    /**
     * Find the dashboard route mirrored by the given api.dashboard.* route name.
     */
    public static function dashboardRoute(?string $name): ?Route
    {
        if (! $name || ! str_starts_with($name, static::API_PREFIX)) {
            return null;
        }

        return RouteFacade::getRoutes()->getByName(substr($name, strlen(static::API_PREFIX)));
    }

    /**
     * Group conventional form and mutation routes under one permission.
     */
    protected static function resolveName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        $segments = explode('.', $name);
        $action = array_pop($segments);

        $action = match ($action) {
            'store' => 'create',
            'update' => 'edit',
            default => $action,
        };

        return implode('.', [...$segments, $action]);
    }
}
