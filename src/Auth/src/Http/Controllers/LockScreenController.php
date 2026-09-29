<?php

namespace Redot\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Redot\Auth\Http\Middleware\Locked;
use Redot\Auth\Panel;
use Redot\Auth\Responses\UnlockedResponse;
use Redot\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class LockScreenController extends Controller
{
    /**
     * Lock the current session behind the unlock screen.
     */
    public function lock(Request $request, Panel $panel): RedirectResponse
    {
        $request->session()->put(Locked::sessionKey($panel->getGuard()), $panel->routeName('unlock'));
        $request->session()->put('url.intended', url()->previous());

        return redirect()->route($panel->routeName('unlock'));
    }

    /**
     * Show the unlock screen.
     */
    public function show(Request $request, Panel $panel): View|RedirectResponse
    {
        if (! $request->session()->has(Locked::sessionKey($panel->getGuard()))) {
            return redirect($panel->homeUrl());
        }

        return $panel->view('unlock', ['user' => $request->user($panel->getGuard())]);
    }

    /**
     * Unlock the session by re-verifying the user's password.
     */
    public function unlock(Request $request, Panel $panel): Response
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user($panel->getGuard());

        if (! Hash::check((string) $request->input('password'), (string) $user->getAuthPassword())) {
            return back()->withErrors(['password' => __('auth.password')]);
        }

        $request->session()->forget(Locked::sessionKey($panel->getGuard()));

        return $panel->make(UnlockedResponse::class)->toResponse($panel);
    }
}
