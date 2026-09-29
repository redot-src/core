<?php

namespace Redot\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Redot\Auth\Panel;
use Redot\Auth\RedotAuthManager;
use Symfony\Component\HttpFoundation\Response;

class ResolvePanel
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected RedotAuthManager $auth) {}

    /**
     * Build the middleware definition for the given panel and route name prefix.
     */
    public static function using(string $panel, string $prefix = ''): string
    {
        return static::class . ':' . $panel . ',' . $prefix;
    }

    /**
     * Attach the panel the route belongs to onto the request.
     */
    public function handle(Request $request, Closure $next, string $panel, string $prefix = ''): Response
    {
        $request->attributes->set(Panel::class, $this->auth->get($panel)->withRoutePrefix($prefix));

        return $next($request);
    }
}
