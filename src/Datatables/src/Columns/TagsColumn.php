<?php

namespace Redot\Datatables\Columns;

use Illuminate\Database\Eloquent\Model;

class TagsColumn extends Column
{
    /**
     * Determine if the column content is HTML.
     */
    public bool $html = true;

    /**
     * Tags limit per row.
     */
    public int $limit = 3;

    /**
     * Tags ellipsis text.
     */
    public string $ellipsis = '...';

    /**
     * Set the tags limit per row.
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the tags ellipsis text.
     */
    public function ellipsis(string $ellipsis): static
    {
        $this->ellipsis = $ellipsis;

        return $this;
    }

    /**
     * Default getter for the column.
     */
    protected function defaultGetter(mixed $value, Model $row): mixed
    {
        if (empty($value)) {
            return null;
        }

        $value = collect($value);
        $tags = $value->take($this->limit)->when($value->count() > $this->limit, fn ($collection) => $collection->push($this->ellipsis));
        $tags = $tags->map(fn ($tag) => sprintf('<span class="tag">%s</span>', e($tag)))->join('');

        return sprintf('<div class="tag-list">%s</div>', $tags);
    }
}
