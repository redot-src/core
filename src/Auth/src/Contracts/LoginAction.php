<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redot\Auth\AuthContext;

interface LoginAction
{
    /**
     * Authenticate the request, starting a session or issuing a bearer token.
     */
    public function authenticate(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
