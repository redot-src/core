<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\AuthContext;

interface EmailVerificationAction
{
    /**
     * Show the verification prompt, or continue home when already verified.
     */
    public function prompt(Request $request, AuthContext $context): RedirectResponse|View;

    /**
     * Mark the user's email as verified from a signed link.
     */
    public function verify(Request $request, AuthContext $context): RedirectResponse|JsonResponse;

    /**
     * Resend the email verification notification.
     */
    public function send(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
