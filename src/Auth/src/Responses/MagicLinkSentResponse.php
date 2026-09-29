<?php

namespace Redot\Auth\Responses;

use Redot\Auth\Panel;
use Symfony\Component\HttpFoundation\Response;

class MagicLinkSentResponse extends AuthResponse
{
    /**
     * Create a new response for the identifier the link was requested with.
     */
    public function __construct(public string $identifier) {}

    /**
     * Send the user to the one-time code screen.
     */
    protected function web(Panel $panel): Response
    {
        return redirect()->route($panel->routeName('magic-link-code.create'), [
            'email' => base64_encode($this->identifier),
        ]);
    }
}
