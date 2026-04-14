<?php

namespace Redot\Auth\Contracts;

use Redot\Auth\AuthContext;

interface RouteRegistrar
{
    public function register(AuthContext $context): void;
}
