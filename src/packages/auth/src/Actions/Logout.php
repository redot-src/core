<?php

namespace Redot\Auth\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\LogoutAction;
use Redot\Traits\RespondAsApi;

class Logout implements LogoutAction
{
    use RespondAsApi;

    public function logout(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        if ($context->api) {
            $request->user()?->currentAccessToken()?->delete();

            return $this->respond(message: 'Logged out successfully.');
        }

        Auth::guard($context->guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($context->homeUrl());
    }
}
