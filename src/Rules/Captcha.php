<?php

namespace Redot\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

class Captcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $success = $this->verifyCloudflareResponse($value);

        if (! $success) {
            $fail(__('validation.captcha'));
        }
    }

    /**
     * Check if the validation rule passes.
     */
    public function passes(string $attribute, mixed $value): bool
    {
        return $this->verifyCloudflareResponse($value);
    }

    /**
     * Verify the Cloudflare response.
     */
    protected function verifyCloudflareResponse($token): bool
    {
        // Pass if the environment is not production
        if (! app()->isProduction()) {
            return true;
        }

        // Verify the token
        $secret = setting('cloudflare_turnstile_secret_key');
        $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

        // Early return if the secret key is not set
        if (! $secret) {
            return false;
        }

        $response = Http::post($endpoint, [
            'secret' => $secret,
            'response' => $token,
        ]);

        $response = $response->json();

        return $response['success'];
    }
}
