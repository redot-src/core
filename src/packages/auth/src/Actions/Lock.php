<?php

namespace Redot\Auth\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Redot\Auth\AuthContext;
use Redot\Auth\Concerns\RespondsWithJson;
use Redot\Auth\Contracts\LockAction;

class Lock implements LockAction
{
    use RespondsWithJson;

    public static function sessionKey(string $guard): string
    {
        return "auth.$guard.locked";
    }

    public function lock(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        if ($context->api) {
            return $this->fail('Lock screen is not supported for API guards.', 400);
        }

        $request->session()->put(static::sessionKey($context->guard), true);
        $request->session()->put('url.intended', url()->previous());

        return redirect()->route($context->routeName('unlock'));
    }

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

    public function unlock(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        if ($context->api) {
            return $this->fail('Lock screen is not supported for API guards.', 400);
        }

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
