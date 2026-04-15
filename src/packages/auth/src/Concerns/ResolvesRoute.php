<?php

namespace Redot\Auth\Concerns;

use Illuminate\Support\Facades\Route;

trait ResolvesRoute
{
    public function resolveRoute(string $route): string
    {
        $name = request()->route()->getName() ?? '';
        $prefix = str($name)->before('.')->append('.');

        $prefixed = $prefix . $route;

        return Route::has($prefixed) ? $prefixed : $route;
    }
}
