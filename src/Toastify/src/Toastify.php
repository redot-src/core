<?php

namespace Redot\Toastify;

use Illuminate\Support\Facades\Session;

/**
 * @method void toast(string $title, ?string $message = null, array $options = [])
 * @method void error(string $title, ?string $message = null, array $options = [])
 * @method void success(string $title, ?string $message = null, array $options = [])
 * @method void info(string $title, ?string $message = null, array $options = [])
 * @method void warning(string $title, ?string $message = null, array $options = [])
 */
class Toastify
{
    /**
     * Push message to session.
     */
    protected function push(string $title, ?string $message, string $type, array $options = []): void
    {
        Session::push('toastify', compact('title', 'message', 'type', 'options'));
    }

    /**
     * Magic method to call toast methods.
     */
    public function __call(string $name, array $arguments)
    {
        [$title, $message, $options] = static::normalizeArguments($arguments);

        $this->push($title, $message, $name, $options);
    }

    /**
     * Normalize positional and named arguments.
     */
    public static function normalizeArguments(array $arguments): array
    {
        $title = $arguments['title'] ?? $arguments[0];
        $message = $arguments['message'] ?? $arguments[1] ?? null;
        $options = $arguments['options'] ?? $arguments[2] ?? [];

        if (is_array($message) && $options === []) {
            $options = $message;
            $message = null;
        }

        return [$title, $message, $options];
    }
}
