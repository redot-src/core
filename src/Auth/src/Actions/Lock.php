<?php

namespace Redot\Auth\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\LockAction;

class Lock implements LockAction
{
    /**
     * Build the session key that marks the given guard as locked.
     */
    public static function sessionKey(string $guard): string
    {
        return "auth.$guard.locked";
    }

    /**
     * Lock the current session behind the unlock screen.
     */
    public function lock(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        $request->session()->put(static::sessionKey($context->guard), true);
        $request->session()->put('url.intended', url()->previous());

        return redirect()->route($context->routeName('unlock'));
    }

    /**
     * Show the unlock screen.
     */
    public function view(Request $request, AuthContext $context): View|RedirectResponse
    {
        if (! $request->session()->has(static::sessionKey($context->guard))) {
            return redirect($context->homeUrl());
        }

        return view($context->views['unlock'], [
            'user' => Auth::guard($context->guard)->user(),
            'context' => $context,
        ]);
    }

    /**
     * Unlock the session by re-verifying the user's password.
     */
    public function unlock(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::guard($context->guard)->user();

        if ($user !== null && Hash::check((string) $request->input('password'), (string) $user->password)) {
            $request->session()->forget(static::sessionKey($context->guard));

            return redirect()->intended($context->homeUrl());
        }

        return back()->withErrors(['password' => __('auth.password')]);
    }
}
