<?php

namespace Redot\Auth\Concerns;

use Closure;
use Redot\Auth\AuthContext;

trait ResolvesValidationRules
{
    /**
     * Custom validation rules, keyed by provider.
     */
    protected static array $rules = [];

    /**
     * Override the validation rules for the given provider.
     */
    public static function validationRules(string $provider, array|Closure $rules): void
    {
        static::$rules[$provider] = $rules;
    }

    /**
     * Resolve the validation rules for the request.
     */
    protected function rules(AuthContext $context): array
    {
        $rules = static::$rules[$context->provider] ?? null;

        if ($rules instanceof Closure) {
            return ($rules)($context);
        }

        if (is_array($rules)) {
            return $rules;
        }

        return $this->defaultRules($context);
    }

    /**
     * Get the default validation rules for the request.
     */
    abstract protected function defaultRules(AuthContext $context): array;
}
