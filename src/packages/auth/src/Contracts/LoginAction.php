<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redot\Auth\AuthContext;

interface LoginAction
{
    public function authenticate(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
