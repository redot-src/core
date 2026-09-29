<?php

namespace Redot\Auth\Responses;

use Illuminate\Contracts\Auth\Authenticatable;
use Redot\Auth\Panel;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse extends AuthResponse
{
    /**
     * Create a new response for the signed-in user and the token issued on API panels.
     */
    public function __construct(public Authenticatable $user, public ?string $token = null) {}

    /**
     * Send the user where they were headed, or home.
     */
    protected function web(Panel $panel): Response
    {
        return redirect()->intended($panel->homeUrl());
    }

    /**
     * Hand the bearer token to the client.
     */
    protected function api(Panel $panel): Response
    {
        return $this->respond(['token' => $this->token, 'token_type' => 'Bearer']);
    }
}
