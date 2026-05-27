<?php

namespace Redot\Toastify\Concerns;

use Redot\Toastify\LivewireToastifier;

/**
 * Adds client-side toast dispatching to Livewire components.
 *
 * Usage: $this->toastify()->success('Saved!');
 */
trait InteractsWithToastify
{
    /**
     * Get a toastifier bound to this Livewire component.
     */
    public function toastify(): LivewireToastifier
    {
        return new LivewireToastifier($this);
    }
}
