<?php

namespace Redot\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Base contract for a two-factor method.
 *
 * A method's lifecycle is: enable() starts setup (pending), confirm() activates
 * it (enabled), verify() checks challenge codes, and disable() turns it off.
 * Methods that deliver codes to the user (email, SMS, ...) also report
 * deliverable() and implement send().
 */
abstract class TwoFactorMethod
{
    /**
     * The unique key identifying the method (e.g. "totp").
     */
    abstract public function key(): string;

    /**
     * Determine whether the user has this method enabled.
     */
    abstract public function enabled(Authenticatable $user): bool;

    /**
     * Begin setup for the user and return the setup payload shown to clients.
     *
     * @return array<string, mixed>
     */
    abstract public function enable(Authenticatable $user): array;

    /**
     * Confirm a pending setup with a verification code.
     */
    abstract public function confirm(Authenticatable $user, string $code): bool;

    /**
     * Disable the method for the user.
     */
    abstract public function disable(Authenticatable $user): void;

    /**
     * Verify a challenge code for the user.
     */
    abstract public function verify(Authenticatable $user, string $code): bool;

    /**
     * Determine whether the user has a setup awaiting confirmation.
     */
    public function pending(Authenticatable $user): bool
    {
        return false;
    }

    /**
     * Whether the method sends codes to the user (shows the send button on the challenge).
     */
    public function deliverable(): bool
    {
        return false;
    }

    /**
     * Send a challenge code to the user.
     */
    public function send(Authenticatable $user): void
    {
        //
    }
}
