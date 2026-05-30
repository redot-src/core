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
    protected function throttleKey(Request $request, AuthContext $context, string $prefix = ''): string
    {
        $inputName = $context->identifierInputName();
        $value = (string) $request->input($inputName);

        $key = Str::transliterate(Str::lower($value) . '|' . $request->ip());

        return $prefix === '' ? $key : $prefix . ':' . $key;
    }

    protected function ensureNotRateLimited(Request $request, AuthContext $context, string $prefix = '', int $attempts = 5, bool $dispatch = true): void
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
}
