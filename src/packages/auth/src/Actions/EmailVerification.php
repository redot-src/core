<?php

namespace Redot\Auth\Actions;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\AuthContext;
use Redot\Auth\Concerns\RespondsWithJson;
use Redot\Auth\Contracts\EmailVerificationAction;

class EmailVerification implements EmailVerificationAction
{
    use RespondsWithJson;

    public function prompt(Request $request, AuthContext $context): RedirectResponse|View
    {
        $verified = $request->user()->hasVerifiedEmail();

        if ($verified) {
            return redirect()->intended($context->homeUrl());
        }

        return view($context->views['verify-email'], ['context' => $context]);
    }

    public function verify(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            if ($context->api) {
                return $this->fail(message: 'Email already verified.', code: 403, payload: [
                    'already_verified' => true,
                ]);
            }

            return redirect()->intended($context->homeUrl() . '?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        if ($context->api) {
            return $this->respond(message: 'Email successfully verified.', payload: [
                'already_verified' => false,
            ]);
        }

        return redirect()->intended($context->homeUrl() . '?verified=1');
    }

    public function send(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            if ($context->api) {
                return $this->fail('Email already verified.', 400);
            }

            return redirect()->intended($context->homeUrl());
        }

        $request->user()->sendEmailVerificationNotification();

        if ($context->api) {
            return $this->respond(message: 'Email verification link sent!');
        }

        return back()->with('success', __('A new verification link has been sent to the email address you provided during registration.'));
    }
}
