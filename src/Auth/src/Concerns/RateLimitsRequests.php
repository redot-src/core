<?php

namespace Redot\Auth\Concerns;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Redot\Auth\AuthContext;

trait RateLimitsRequests
{
    /**
     * Build the rate limiter key for the request.
     */
    protected function throttleKey(Request $request, AuthContext $context, string $prefix = ''): string
    {
        $inputName = $context->identifierInputName();
        $value = (string) $request->input($inputName);

        $key = Str::transliterate(Str::lower($value) . '|' . $request->ip());

        return $prefix === '' ? $key : $prefix . ':' . $key;
    }

    /**
     * Abort with a throttle error when too many attempts have been made.
     */
    protected function throttle(Request $request, AuthContext $context, string $prefix = '', int $attempts = 5, bool $dispatch = true): void
    {
        $key = $this->throttleKey($request, $context, $prefix);

        if (! RateLimiter::tooManyAttempts($key, $attempts)) {
            return;
        }

        if ($dispatch) {
            event(new Lockout($request));
        }

        $seconds = RateLimiter::availableIn($key);
        $inputName = $context->identifierInputName();

        throw ValidationException::withMessages([
            $inputName => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Record a failed attempt and abort with a failed-authentication error.
     */
    protected function reject(Request $request, AuthContext $context, string $prefix = '', int $decaySeconds = 60): never
    {
        RateLimiter::hit($this->throttleKey($request, $context, $prefix), $decaySeconds);

        throw ValidationException::withMessages([
            $context->identifierInputName() => __('auth.failed'),
        ]);
    }

    /**
     * Clear the rate limiter for the request.
     */
    protected function clear(Request $request, AuthContext $context, string $prefix = ''): void
    {
        RateLimiter::clear($this->throttleKey($request, $context, $prefix));
    }
}
