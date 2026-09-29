<?php

namespace Redot\Auth\Responses;

use Redot\Auth\Panel;
use Symfony\Component\HttpFoundation\Response;

class ResetLinkSentResponse extends AuthResponse
{
    /**
     * Create a new response with the password broker's status.
     */
    public function __construct(public string $status) {}

    /**
     * Return to the form with the status message.
     */
    protected function web(Panel $panel): Response
    {
        return back()->with('success', __($this->status));
    }

    /**
     * Return the status message.
     */
    protected function api(Panel $panel): Response
    {
        return $this->respond(message: __($this->status));
    }
}
