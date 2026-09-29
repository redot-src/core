<?php

namespace Redot\Auth\Pipeline;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Redot\Auth\Concerns\ThrottlesAttempts;
use Redot\Auth\Panel;

class LoginAttempt
{
    use ThrottlesAttempts;

    /**
     * The bearer token issued to the user on API panels.
     */
    public ?string $token = null;

    /**
     * Create a new login attempt instance.
     */
    public function __construct(
        public readonly Request $request,
        public readonly Panel $panel,
        public readonly string $identifier,
        public readonly bool $remember = false,
        public (Model&Authenticatable)|null $user = null,
    ) {}

    /**
     * Create a login attempt from the submitted credentials.
     */
    public static function fromRequest(Request $request, Panel $panel): static
    {
        return new static(
            request: $request,
            panel: $panel,
            identifier: (string) $request->input($panel->identifierInputName()),
            remember: $request->boolean('remember'),
        );
    }

    /**
     * Get the rate limiter key for the attempt.
     */
    public function key(): string
    {
        return $this->throttleKey('login', $this->identifier, $this->request->ip());
    }
}
