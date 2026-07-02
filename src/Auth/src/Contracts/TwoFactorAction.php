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
    public function redirectToChallenge(Request $request, Authenticatable $user, AuthContext $context): RedirectResponse|JsonResponse;

    public function challenge(Request $request, AuthContext $context): View|RedirectResponse;

    public function verify(Request $request, AuthContext $context): RedirectResponse|JsonResponse;

    public function send(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse;

    public function edit(Request $request, AuthContext $context): View|JsonResponse;

    public function store(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse;

    public function confirm(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse;

    public function destroy(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse;

    public function recoveryCodes(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
