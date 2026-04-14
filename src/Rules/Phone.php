<?php

namespace Redot\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class Phone implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected $country = 'EG',
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->passes($attribute, $value)) {
            return;
        }

        $fail('validation.phone')->translate();
    }

    /**
     * Check if the validation rule passes.
     */
    public function passes(string $attribute, mixed $value): bool
    {
        $instance = PhoneNumberUtil::getInstance();

        try {
            $phone = $instance->parse($value, $this->country);

            return $instance->isValidNumber($phone);
        } catch (NumberParseException) {
            return false;
        }
    }
}
