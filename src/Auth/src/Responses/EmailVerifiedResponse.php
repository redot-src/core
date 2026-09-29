<?php

namespace Redot\Auth\Responses;

use Illuminate\Support\Uri;
use Redot\Auth\Panel;
use Symfony\Component\HttpFoundation\Response;

class EmailVerifiedResponse extends AuthResponse
{
    /**
     * Create a new response, noting whether the email was verified before.
     */
    public function __construct(public bool $alreadyVerified) {}

    /**
     * Send the user home with a verified flag.
     */
    protected function web(Panel $panel): Response
    {
        return redirect()->intended(Uri::of($panel->homeUrl())->withQuery(['verified' => 1])->value());
    }

    /**
     * Report the verification, or refuse when it was already done.
     */
    protected function api(Panel $panel): Response
    {
        if ($this->alreadyVerified) {
            return $this->fail('Email already verified.', 403, ['already_verified' => true]);
        }

        return $this->respond(['already_verified' => false], 'Email successfully verified.');
    }
}
