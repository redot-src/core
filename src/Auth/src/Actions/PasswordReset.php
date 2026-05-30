<?php

namespace Redot\Auth\Actions;

use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\PasswordResetAction;
use Redot\Traits\RespondAsApi;

class PasswordReset implements PasswordResetAction
{
    use RespondAsApi;

    public function sendResetLink(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        Password::broker($context->broker)->sendResetLink($request->only('email'));

        if ($context->api) {
            return $this->respond(message: __(Password::RESET_LINK_SENT));
        }

        return back()->with('success', __(Password::RESET_LINK_SENT));
    }

    public function reset(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', 'min:8', Rules\Password::defaults()],
        ]);

        $status = Password::broker($context->broker)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request): void {
                $user->forceFill([
                    'password' => $request->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordResetEvent($user));
            }
        );

        if ($context->api) {
            if ($status !== Password::PASSWORD_RESET) {
                throw ValidationException::withMessages([
                    'email' => [__($status)],
                ]);
            }

            return $this->respond(message: __($status));
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route($context->routeName('login'))->with('success', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
