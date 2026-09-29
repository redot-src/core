<?php

namespace Redot\Auth\Pipeline;

use Closure;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Redot\Auth\Concerns\ThrottlesAttempts;
use Redot\Auth\Features\Login;

class EnsureLoginIsNotThrottled
{
    use ThrottlesAttempts;

    /**
     * Abort when the attempt exceeds the failed login limit.
     */
    public function handle(LoginAttempt $attempt, Closure $next): mixed
    {
        $maxAttempts = $attempt->panel->feature(Login::class)->getMaxAttempts();

        if (RateLimiter::tooManyAttempts($attempt->key(), $maxAttempts)) {
            event(new Lockout($attempt->request));

            $this->throwThrottled($attempt->key(), $attempt->panel->identifierInputName());
        }

        return $next($attempt);
    }
}
