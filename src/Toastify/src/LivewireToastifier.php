<?php

namespace Redot\Toastify;

use Livewire\Component;

/**
 * @method void toast(string $title, ?string $message = null, array $options = [])
 * @method void error(string $title, ?string $message = null, array $options = [])
 * @method void success(string $title, ?string $message = null, array $options = [])
 * @method void info(string $title, ?string $message = null, array $options = [])
 * @method void warning(string $title, ?string $message = null, array $options = [])
 */
class LivewireToastifier
{
    /**
     * Create a new LivewireToastifier instance.
     */
    public function __construct(protected Component $component) {}

    /**
     * Dispatch a toast to the browser from the Livewire component.
     */
    public function __call(string $name, array $arguments): void
    {
        [$title, $message, $options] = Toastify::normalizeArguments($arguments);

        $this->component->dispatch('toastify', $title, $message, $name, $options);
    }
}
