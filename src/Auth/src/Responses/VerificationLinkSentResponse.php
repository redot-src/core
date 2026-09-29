<?php

namespace Redot\Auth\Responses;

use Redot\Auth\Panel;
use Symfony\Component\HttpFoundation\Response;

class VerificationLinkSentResponse extends AuthResponse
{
    /**
     * Create a new response, noting whether the email was verified before.
     */
    public function __construct(public bool $alreadyVerified) {}

    /**
     * Confirm the link was sent, or send verified users home.
     */
    protected function web(Panel $panel): Response
    {
        if ($this->alreadyVerified) {
            return redirect()->intended($panel->homeUrl());
        }

        return back()->with('success', __('A new verification link has been sent to the email address you provided during registration.'));
    }

    /**
     * Confirm the link was sent, or refuse when the email is already verified.
     */
    protected function api(Panel $panel): Response
    {
        if ($this->alreadyVerified) {
            return $this->fail('Email already verified.', 400);
        }

        return $this->respond(message: 'Email verification link sent!');
    }
}
