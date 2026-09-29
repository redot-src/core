<?php

namespace Redot\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class Locked
{
    /**
     * Build the middleware definition for the given guard.
     */
    public static function using(string $guard): string
    {
        return static::class . ':' . $guard;
    }

    /**
     * Build the session key that holds the unlock route of a locked guard.
     */
    public static function sessionKey(string $guard): string
    {
        return "auth.$guard.locked";
    }

    /**
     * Send locked sessions to the unlock screen they were locked from.
     */
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        $key = static::sessionKey($guard);
        $unlockRoute = $request->session()->get($key);

        if (! is_string($unlockRoute)) {
            return $next($request);
        }

        // A lock left behind by a signed-out user or a removed unlock route would trap them in redirects.
        if (Auth::guard($guard)->guest() || ! Route::has($unlockRoute)) {
            $request->session()->forget($key);

            return $next($request);
        }

        if ($request->routeIs($unlockRoute, $unlockRoute . '.store')) {
            return $next($request);
        }

        $request->session()->put('url.intended', url()->previous());

        return redirect()->route($unlockRoute);
    }
}
