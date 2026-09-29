<?php

namespace Redot\Auth\Responses;

use LogicException;
use Redot\Auth\Panel;
use Redot\Traits\RespondAsApi;
use Symfony\Component\HttpFoundation\Response;

abstract class AuthResponse
{
    use RespondAsApi;

    /**
     * Build the browser or API response, depending on the panel.
     */
    public function toResponse(Panel $panel): Response
    {
        return $panel->isApi() ? $this->api($panel) : $this->web($panel);
    }

    /**
     * Build the response for browser panels.
     */
    protected function web(Panel $panel): Response
    {
        throw new LogicException(static::class . ' has no browser response.');
    }

    /**
     * Build the response for API panels.
     */
    protected function api(Panel $panel): Response
    {
        throw new LogicException(static::class . ' has no API response.');
    }
}
