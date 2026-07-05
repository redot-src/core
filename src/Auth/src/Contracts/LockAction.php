<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\AuthContext;

interface LockAction
{
    /**
     * Lock the current session behind the unlock screen.
     */
    public function lock(Request $request, AuthContext $context): RedirectResponse|JsonResponse;

    /**
     * Show the unlock screen.
     */
    public function view(Request $request, AuthContext $context): View|RedirectResponse;

    /**
     * Unlock the session by re-verifying the user's password.
     */
    public function unlock(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
