<?php

namespace Redot\Auth\Facades;

use Illuminate\Support\Facades\Facade;
use Redot\Auth\RedotAuthManager;

/**
 * @method static void routes(string $guard, ?\Closure $scope = null, array $views = [], array $disable = [], array $registrars = [], ?string $home = null)
 */
class RedotAuth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RedotAuthManager::class;
    }
}
