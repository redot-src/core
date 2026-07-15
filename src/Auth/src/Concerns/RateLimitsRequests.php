<?php

namespace Redot\Auth\Concerns;

use Carbon\CarbonInterval;
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
        $value = (string) $request->input($context->identifierInputName());

        return $this->throttleKeyFor($value, $request->ip(), $prefix);
    }

    /**
     * Build the rate limiter key for the given identifier and IP address.
     */
    protected function throttleKeyFor(string $identifier, ?string $ip, string $prefix = ''): string
    {
        $key = Str::transliterate(Str::lower($identifier) . '|' . $ip);

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
            $inputName => __('Too many attempts. Please try again in :time.', [
                'time' => CarbonInterval::seconds($seconds)->cascade()->forHumans(['parts' => 2, 'join' => true]),
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
