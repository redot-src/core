<?php

namespace Redot\Auth\Responses;

use Redot\Auth\Panel;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse extends AuthResponse
{
    /**
     * Send the signed-out user home.
     */
    protected function web(Panel $panel): Response
    {
        return redirect($panel->homeUrl());
    }

    /**
     * Confirm the token was revoked.
     */
    protected function api(Panel $panel): Response
    {
        return $this->respond(message: 'Logged out successfully.');
    }
}
