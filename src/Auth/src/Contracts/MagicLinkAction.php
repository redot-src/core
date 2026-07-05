<?php

namespace Redot\Auth\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\AuthContext;

interface MagicLinkAction
{
    /**
     * Email a magic link and one-time code to the user.
     */
    public function send(Request $request, AuthContext $context): RedirectResponse;

    /**
     * Sign the user in from an emailed link token.
     */
    public function verify(string $token, AuthContext $context): RedirectResponse;

    /**
     * Show the one-time code entry screen.
     */
    public function view(Request $request, AuthContext $context): View|RedirectResponse;

    /**
     * Sign the user in from a submitted one-time code.
     */
    public function confirm(Request $request, AuthContext $context): RedirectResponse;
}
