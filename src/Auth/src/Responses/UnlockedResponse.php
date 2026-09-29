<?php

namespace Redot\Auth\Responses;

use Redot\Auth\Panel;
use Symfony\Component\HttpFoundation\Response;

class UnlockedResponse extends AuthResponse
{
    /**
     * Send the user back to where they were before locking.
     */
    protected function web(Panel $panel): Response
    {
        return redirect()->intended($panel->homeUrl());
    }
}
