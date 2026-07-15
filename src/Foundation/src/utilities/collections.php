<?php

use Illuminate\Support\Collection;

/**
 * Collect the given array with ellipsis.
 */
function collect_ellipsis($value = [], int $limit = 3, ?string $ellipsis = '...'): Collection
{
    $collection = collect($value);
    $count = $collection->count();

    return $collection
        ->take($limit)
        ->when($count > $limit, function ($collection) use ($count, $limit, $ellipsis) {
            return $collection->push(__($ellipsis, ['count' => $count - $limit]));
        });
}
