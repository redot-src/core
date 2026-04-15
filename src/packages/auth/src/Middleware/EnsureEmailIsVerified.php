<?php

namespace Redot\Auth\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Redot\Auth\Concerns\ResolvesRoute;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    use ResolvesRoute;

    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        $user = $request->user();

        if ($user && (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail())) {
            return $next($request);
        }

        return $request->expectsJson()
            ? abort(403, 'Your email address is not verified.')
            : Redirect::guest(URL::route($redirectToRoute ?? $this->resolveRoute('verification.notice')));
    }

    public static function redirectTo(string $route): string
    {
        return static::class . ':' . $route;
    }
}
