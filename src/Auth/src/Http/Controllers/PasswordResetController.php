<?php

namespace Redot\Auth\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Redot\Auth\Panel;
use Redot\Auth\Responses\PasswordResetResponse;
use Redot\Auth\Responses\ResetLinkSentResponse;
use Redot\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

class PasswordResetController extends Controller
{
    /**
     * Show the forgot-password screen.
     */
    public function request(Panel $panel): View
    {
        return $panel->view('forgot-password');
    }

    /**
     * Email a password reset link to the user.
     */
    public function email(Request $request, Panel $panel): Response
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Send after the response and always report success, so neither the timing
        // nor the message reveals which emails exist.
        $credentials = $request->only('email');

        defer(fn () => Password::broker($panel->broker())->sendResetLink($credentials));

        return $panel->make(ResetLinkSentResponse::class, ['status' => Password::RESET_LINK_SENT])->toResponse($panel);
    }

    /**
     * Show the reset-password screen.
     */
    public function edit(Request $request, Panel $panel): View
    {
        return $panel->view('reset-password', ['request' => $request]);
    }

    /**
     * Reset the user's password from a valid reset token.
     */
    public function update(Request $request, Panel $panel): Response
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8', Rules\Password::defaults()],
        ]);

        $status = Password::broker($panel->broker())->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => $request->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return $panel->make(PasswordResetResponse::class, ['status' => $status])->toResponse($panel);
    }
}
