<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\View;

/**
 * Render the given component.
 */
function component(string $name, array $data = []): string|View
{
    // Define the base namespace for components
    $baseNamespace = app()->getNamespace() . 'View\\Components\\';

    // Convert the name to a fully qualified class name
    $className = class_exists($name) ? $name : $baseNamespace . str_replace(' ', '\\', ucwords(str_replace('.', ' ', $name)));

    if (! class_exists($className)) {
        // If the class does not exist, render the view as an inline component
        return view("components.$name", $data);
    }

    // Create a new component instance and render it
    return Blade::renderComponent(app()->make($className, $data));
}

/**
 * Render the no content message.
 */
function no_content(): string
{
    return '<p class="text-muted">' . __('No content') . '</p>';
}

/**
 * Render a badge for the given boolean value.
 */
function switch_badge(mixed $value, ?string $true = null, ?string $false = null): string
{
    $true = $true ?: __('Yes');
    $false = $false ?: __('No');

    return $value ? '<span class="badge bg-success-lt">' . $true . '</span>' : '<span class="badge bg-danger-lt">' . $false . '</span>';
}
