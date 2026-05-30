<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\AuthContext;

interface LockAction
{
    public function lock(Request $request, AuthContext $context): RedirectResponse|JsonResponse;

    public function view(Request $request, AuthContext $context): View|RedirectResponse;

    public function unlock(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
