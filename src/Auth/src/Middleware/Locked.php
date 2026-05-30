<?php

namespace Redot\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Redot\Auth\Actions\Lock;
use Symfony\Component\HttpFoundation\Response;

class Locked
{
    public function handle(Request $request, Closure $next, string $guard = 'web', string $unlockRoute = 'unlock'): Response
    {
        if ($request->session()->get(Lock::sessionKey($guard))) {
            if (! $request->routeIs($unlockRoute)) {
                $request->session()->put('url.intended', url()->previous());
            }

            return redirect()->route($unlockRoute);
        }

        return $next($request);
    }
}
