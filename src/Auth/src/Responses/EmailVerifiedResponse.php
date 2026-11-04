<?php

namespace Redot\Auth\Responses;

use Redot\Auth\Features\EmailVerification;
use Redot\Auth\Panel;
use Symfony\Component\HttpFoundation\Response;

class EmailVerifiedResponse extends AuthResponse
{
    /**
     * Create a new response, noting whether the email was verified before.
     */
    public function __construct(public bool $alreadyVerified) {}

    /**
     * Send the user where they were headed, or to the feature's redirect, with a success message.
     */
    protected function web(Panel $panel): Response
    {
        return redirect()->intended($panel->feature(EmailVerification::class)->getRedirectUrl($panel))
            ->with('success', __('Your email address has been verified.'));
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
