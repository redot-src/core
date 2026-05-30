<?php

namespace Redot\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoutePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()->getName();

        if (! $name || route_allowed($name)) {
            return $next($request);
        }

        abort(403);
    }
}
