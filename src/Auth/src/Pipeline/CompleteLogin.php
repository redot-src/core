<?php

namespace Redot\Auth\Pipeline;

use Closure;
use Illuminate\Support\Facades\RateLimiter;
use Redot\Auth\Authenticator;

class CompleteLogin
{
    /**
     * Clear the login throttle and sign the user in.
     */
    public function handle(LoginAttempt $attempt, Closure $next): mixed
    {
        RateLimiter::clear($attempt->key());

        $attempt->token = $attempt->panel->make(Authenticator::class)->login($attempt->request, $attempt->panel, $attempt->user, $attempt->remember);

        return $next($attempt);
    }
}
