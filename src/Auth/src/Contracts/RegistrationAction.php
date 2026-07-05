<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redot\Auth\AuthContext;

interface RegistrationAction
{
    /**
     * Register a new user, starting a session or issuing a bearer token.
     */
    public function register(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
