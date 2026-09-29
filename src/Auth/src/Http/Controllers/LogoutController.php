<?php

namespace Redot\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Redot\Auth\Panel;
use Redot\Auth\Responses\LogoutResponse;
use Redot\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class LogoutController extends Controller
{
    /**
     * Log the user out, revoking the access token or invalidating the session.
     */
    public function __invoke(Request $request, Panel $panel): Response
    {
        if ($panel->isApi()) {
            $request->user($panel->getGuard())?->currentAccessToken()?->delete();
        } else {
            Auth::guard($panel->getGuard())->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $panel->make(LogoutResponse::class)->toResponse($panel);
    }
}
