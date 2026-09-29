<?php

namespace Redot\Auth\Features;

use Redot\Auth\Panel;

abstract class Feature
{
    /**
     * Create a new instance of the feature.
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * Register the feature's routes for the given panel.
     */
    abstract public function routes(Panel $panel): void;

    /**
     * Adjust the steps run once a user's identity is verified, before they are signed in.
     */
    public function verifiedSteps(array $steps): array
    {
        return $steps;
    }

    /**
     * Determine whether the feature can be enabled on API panels.
     */
    public function supportsApi(): bool
    {
        return true;
    }
}
