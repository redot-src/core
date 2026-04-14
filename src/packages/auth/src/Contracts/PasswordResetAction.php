<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redot\Auth\AuthContext;

interface PasswordResetAction
{
    public function sendResetLink(Request $request, AuthContext $context): RedirectResponse|JsonResponse;

    public function reset(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
