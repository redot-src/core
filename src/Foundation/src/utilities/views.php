<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;

/**
 * Render the given component, the same way `<x-name>` would.
 */
function component(string $name, array $data = []): string
{
    return Blade::render(
        '<x-dynamic-component :component="$componentName" :attributes="new \\' . ComponentAttributeBag::class . '($componentData)" />',
        ['componentName' => $name, 'componentData' => $data],
    );
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
