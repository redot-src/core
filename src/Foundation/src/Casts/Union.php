<?php

namespace Redot\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Union implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        switch ($value) {
            case 'false': return false;
            case 'true': return true;
        }

        if (is_numeric($value)) {
            return $this->castNumericString($value);
        }

        if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return $value;
    }

    /**
     * Cast a numeric string to int or float when it round-trips safely.
     *
     * Leading-zero strings (e.g. phone numbers) and integers that overflow
     * PHP's int range are left as strings.
     */
    protected function castNumericString(mixed $value): int|float|string
    {
        // Early return if value is already an int or float
        if (is_int($value) || is_float($value)) return $value;

        // If value is a string and starts with a leading zero, return it as is
        if (preg_match('/^[-+]?0\d/', $value)) return $value;

        // If value is a string and contains a dot or e, return it as a float
        if (str_contains($value, '.') || stripos($value, 'e') !== false) return (float) $value;

        $int = (int) $value;

        return (string) $int === $value || (string) $int === ltrim($value, '+') ? $int : $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $value;
    }
}
