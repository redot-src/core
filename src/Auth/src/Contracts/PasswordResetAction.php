<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redot\Auth\AuthContext;

interface PasswordResetAction
{
    /**
     * Email a password reset link to the user.
     */
    public function send(Request $request, AuthContext $context): RedirectResponse|JsonResponse;

    /**
     * Reset the user's password from a valid reset token.
     */
    public function reset(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
