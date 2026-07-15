<?php

/**
 * Parse the given CSV string to an array.
 */
function parse_csv(string|array $csv, ?string $separator = ',', ?callable $callback = null): array
{
    if (is_string($csv)) {
        $csv = explode($separator, $csv);
    }

    return array_values(array_filter(array_map($callback ?: 'trim', $csv), fn ($v) => $v !== '' && $v !== null));
}
