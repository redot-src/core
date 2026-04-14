<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\AuthContext;

interface EmailVerificationAction
{
    public function prompt(Request $request, AuthContext $context): RedirectResponse|View;

    public function verify(Request $request, AuthContext $context): RedirectResponse|JsonResponse;

    public function send(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
