<?php

namespace Redot\Auth\Facades;

use Illuminate\Support\Facades\Facade;
use Redot\Auth\RedotAuthManager;

/**
 * @method static \Redot\Auth\Panel panel(string $name)
 * @method static \Redot\Auth\Panel get(string $name)
 * @method static bool hasPanel(string $name)
 * @method static void routes(string $name)
 * @method static void protectLockedGroups()
 * @method static \Redot\Auth\Panel current(\Illuminate\Http\Request $request)
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
