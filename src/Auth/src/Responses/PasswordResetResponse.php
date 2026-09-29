<?php

namespace Redot\Auth\Responses;

use Redot\Auth\Features\Login;
use Redot\Auth\Panel;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetResponse extends AuthResponse
{
    /**
     * Create a new response with the password broker's status.
     */
    public function __construct(public string $status) {}

    /**
     * Send the user to the login screen, or home when the panel has none, with the status message.
     */
    protected function web(Panel $panel): Response
    {
        $url = $panel->hasFeature(Login::class) ? route($panel->routeName('login')) : $panel->homeUrl();

        return redirect($url)->with('success', __($this->status));
    }

    /**
     * Return the status message.
     */
    protected function api(Panel $panel): Response
    {
        return $this->respond(message: __($this->status));
    }
}
