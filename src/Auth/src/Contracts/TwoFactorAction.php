<?php

namespace Redot\Auth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\AuthContext;

interface TwoFactorAction
{
    /**
     * Park the verified login and hand off to the challenge: web guards are
     * redirected to the challenge screen, API guards receive a challenge token.
     */
    public function redirectToChallenge(Request $request, Authenticatable $user, AuthContext $context): RedirectResponse|JsonResponse;

    /**
     * Render the challenge screen for the pending login, or redirect
     * to the login screen when no challenge is pending. Web guards only.
     */
    public function challenge(Request $request, AuthContext $context): View|RedirectResponse;

    /**
     * Verify the challenge with a method code or a recovery code, completing
     * the login with a session (web) or a bearer token (API) on success.
     */
    public function verify(Request $request, AuthContext $context): RedirectResponse|JsonResponse;

    /**
     * Send a challenge code to the user through a deliverable method.
     */
    public function send(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse;

    /**
     * Show the two-factor settings screen (web) or the per-method
     * enabled/pending/deliverable state map (API).
     */
    public function edit(Request $request, AuthContext $context): View|JsonResponse;

    /**
     * Begin setup for the given method, returning the method's setup payload
     * to API guards (e.g. the totp secret and otpauth URL).
     */
    public function store(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse;

    /**
     * Confirm a pending method setup with a verification code, issuing
     * recovery codes when the user's first method is confirmed.
     */
    public function confirm(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse;

    /**
     * Disable the given method (requires the current password), or cancel
     * its pending setup (no password needed).
     */
    public function destroy(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse;

    /**
     * Regenerate the user's recovery codes. Requires the current password.
     */
    public function recoveryCodes(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
