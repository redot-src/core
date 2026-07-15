<?php

namespace Redot\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class PermissionNameResolver
{
    public const ACTION_KEY = 'redot.permission';

    public const RESOURCE_ACTIONS_KEY = 'metadata.redot.permissions';

    /**
     * Resolve the permission name used by the given route.
     */
    public static function resolve(Route|string $route): ?string
    {
        if (is_string($route) && $registeredRoute = static::route($route)) {
            return static::resolve($registeredRoute);
        }

        if (is_string($route)) {
            return static::resolveName($route);
        }

        if ($permission = $route->getAction(static::ACTION_KEY)) {
            return $permission;
        }

        $permissions = $route->getAction(static::RESOURCE_ACTIONS_KEY);

        if (is_array($permissions) && $permission = $permissions[$route->getActionMethod()] ?? null) {
            return $permission;
        }

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
