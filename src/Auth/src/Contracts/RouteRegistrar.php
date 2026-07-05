<?php

namespace Redot\Auth\Contracts;

use Redot\Auth\AuthContext;

interface RouteRegistrar
{
    /**
     * Register the feature's routes for the given context.
     */
    public function register(AuthContext $context): void;
}
