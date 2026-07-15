<?php

namespace Redot\Datatables\Columns;

use Illuminate\Database\Eloquent\Model;

class ColorColumn extends Column
{
    /**
     * The column's width.
     */
    public string $width = '50px';

    /**
     * Determine if the column is exportable.
     */
    public bool $exportable = false;

    /**
     * Prepare the attributes before building.
     */
    protected function prepareAttributes(?Model $row = null): void
    {
        parent::prepareAttributes($row);

        $color = $this->get($row, true);

        $css = [
            'color: transparent',
            'user-select: none',
        ];

        if (is_string($color) && $this->isValidColor(trim($color))) {
            $css[] = 'background-color: ' . trim($color);
        }

        $this->css($css);
    }

    /**
     * Determine if the value is a plain CSS color, rejecting
     * anything that could smuggle in extra declarations. Function
     * arguments exclude ";" and ":" so no declaration can be injected.
     */
    protected function isValidColor(string $color): bool
    {
        return preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $color)
            || preg_match('/^[a-z]+$/i', $color)
            || preg_match('/^(rgba?|hsla?|hwb|lab|lch|oklab|oklch|color|color-mix|var)\([\w#,.%\s\/()-]*\)$/i', $color);
    }
}
