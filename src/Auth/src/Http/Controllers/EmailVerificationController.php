<?php

namespace Redot\Auth\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\Panel;
use Redot\Auth\Responses\EmailVerifiedResponse;
use Redot\Auth\Responses\VerificationLinkSentResponse;
use Redot\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationController extends Controller
{
    /**
     * Show the verification prompt, or continue home when already verified.
     */
    public function notice(Request $request, Panel $panel): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($panel->homeUrl());
        }

        return $panel->view('verify-email');
    }

    /**
     * Mark the user's email as verified from a signed link.
     */
    public function verify(EmailVerificationRequest $request, Panel $panel): Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $panel->make(EmailVerifiedResponse::class, ['alreadyVerified' => true])->toResponse($panel);
        }

        $request->fulfill();

        return $panel->make(EmailVerifiedResponse::class, ['alreadyVerified' => false])->toResponse($panel);
    }

    /**
     * Resend the email verification notification.
     */
    public function send(Request $request, Panel $panel): Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $panel->make(VerificationLinkSentResponse::class, ['alreadyVerified' => true])->toResponse($panel);
        }

        $request->user()->sendEmailVerificationNotification();

        return $panel->make(VerificationLinkSentResponse::class, ['alreadyVerified' => false])->toResponse($panel);
    }
}
