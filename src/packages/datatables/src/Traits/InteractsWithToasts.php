<?php

namespace Redot\Datatables\Traits;

trait InteractsWithToasts
{
    /**
     * Dispatch a toast event to the frontend.
     */
    protected function toast(string $message, string $type = 'toast', array $options = []): void
    {
        $this->dispatch('datatable-toast', message: $message, type: $type, options: $options);
    }

    /**
     * Dispatch a success toast event to the frontend.
     */
    protected function success(string $message, array $options = []): void
    {
        $this->toast($message, 'success', $options);
    }

    /**
     * Dispatch an error toast event to the frontend.
     */
    protected function error(string $message, array $options = []): void
    {
        $this->toast($message, 'error', $options);
    }

    /**
     * Dispatch an info toast event to the frontend.
     */
    protected function info(string $message, array $options = []): void
    {
        $this->toast($message, 'info', $options);
    }

    /**
     * Dispatch a warning toast event to the frontend.
     */
    protected function warning(string $message, array $options = []): void
    {
        $this->toast($message, 'warning', $options);
    }
}
