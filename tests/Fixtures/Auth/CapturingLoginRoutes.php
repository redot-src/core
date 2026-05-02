<?php

namespace Tests\Fixtures\Auth;

use Redot\Auth\AuthContext;
use Redot\Auth\Routes\LoginRoutes;

class CapturingLoginRoutes extends LoginRoutes
{
    public static ?AuthContext $context = null;

    public function register(AuthContext $context): void
    {
        static::$context = $context;
    }
}
