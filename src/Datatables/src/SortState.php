<?php

namespace Redot\Datatables;

use Illuminate\Support\Collection;

final class SortState
{
    /**
     * Create a new sort state instance.
     *
     * @param  array<string, string>  $sorts  column => direction
     */
    private function __construct(private readonly array $sorts = []) {}

    /**
     * Parse a sort state from its string form (`title,-age`).
     *
     * A leading `-` marks descending; a bare column name is ascending.
     */
    public static function fromString(string $raw): self
    {
        $sorts = collect(explode(',', $raw))
            ->map(fn (string $segment) => trim($segment))
            ->filter()
            ->mapWithKeys(function (string $segment) {
                $descending = str_starts_with($segment, '-');
                $column = $descending ? substr($segment, 1) : $segment;

                return $column === '' ? [] : [$column => $descending ? 'desc' : 'asc'];
            })
            ->all();

        return new self($sorts);
    }

    /**
     * Create an empty sort state.
     */
    public static function empty(): self
    {
        return new self;
    }

    /**
     * Format the sort state back to its string form (`title,-age`).
     */
    public function toString(): string
    {
        return collect($this->sorts)
            ->map(fn (string $direction, string $column) => $direction === 'desc' ? "-{$column}" : $column)
            ->implode(',');
    }

    /**
     * Cycle the column unsorted → asc → desc → unsorted, replacing
     * any other active sort (click behaviour).
     */
    public function cycle(string $column): self
    {
        if (count($this->sorts) === 1 && isset($this->sorts[$column])) {
            return new self($this->sorts[$column] === 'asc' ? [$column => 'desc'] : []);
        }

        return new self([$column => 'asc']);
    }

    /**
     * Cycle the column in place, keeping the other active sorts
     * (shift-click behaviour).
     */
    public function cycleAppend(string $column): self
    {
        $sorts = $this->sorts;

        match ($sorts[$column] ?? null) {
            null => $sorts[$column] = 'asc',
            'asc' => $sorts[$column] = 'desc',
            default => $sorts = array_diff_key($sorts, [$column => true]),
        };

        return new self($sorts);
    }

    /**
     * Get the active sorts keyed by column name.
     */
    public function all(): Collection
    {
        return collect($this->sorts);
    }
}
