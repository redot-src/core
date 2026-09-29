<?php

namespace Redot\Auth\Concerns;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait ThrottlesAttempts
{
    /**
     * Build the rate limiter key for the given identifier and IP address.
     */
    protected function throttleKey(string $prefix, string $identifier, ?string $ip): string
    {
        return $prefix . ':' . Str::transliterate(Str::lower($identifier) . '|' . $ip);
    }

    /**
     * Abort with a validation error telling the user when to retry.
     */
    protected function throwThrottled(string $key, string $field): never
    {
        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            $field => __('Too many attempts. Please try again in :time.', [
                'time' => CarbonInterval::seconds($seconds)->cascade()->forHumans(['parts' => 2, 'join' => true]),
            ]),
        ]);
    }
}
