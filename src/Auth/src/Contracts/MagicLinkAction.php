<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\AuthContext;

interface MagicLinkAction
{
    public function send(Request $request, AuthContext $context): RedirectResponse;

    public function verifyToken(string $token, AuthContext $context): RedirectResponse;

    public function view(Request $request, AuthContext $context): View|RedirectResponse;

    public function verifyCode(Request $request, AuthContext $context): RedirectResponse;
}
