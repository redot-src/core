<?php

namespace Redot\Auth\Facades;

use Illuminate\Support\Facades\Facade;
use Redot\Auth\RedotAuthManager;

/**
 * @method static void routes(string $guard, ?\Closure $scope = null, array $views = [], array $disable = [], array $registrars = [], ?string $home = null)
 *
 * @see RedotAuthManager
 */
class RedotAuth extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return RedotAuthManager::class;
    }
}
