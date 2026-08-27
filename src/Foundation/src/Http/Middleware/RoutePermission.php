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
        $route = $request->route();

        if (! $route->getName() || route_allowed($route)) {
            return $next($request);
        }

        abort(403);
    }
}
