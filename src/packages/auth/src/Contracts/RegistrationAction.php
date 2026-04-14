<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redot\Auth\AuthContext;

interface RegistrationAction
{
    public function register(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
