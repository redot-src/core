<?php

namespace Tests\Fixtures\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Columns\Column;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\SelectFilter;
use Redot\Datatables\Filters\StringFilter;

class BlogCommentsDatatable extends Datatable
{
    protected string $model = BlogComment::class;

    public function columns(): array
    {
        return [
            Column::make('id'),
            Column::make('body')->searchable()->sortable(),
            Column::make('post.title')->searchable()->sortable(),
            Column::make('post.author.name')->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            StringFilter::make('body'),
            SelectFilter::make('post.title')->global(),
        ];
    }

    /**
     * Expose the built query for SQL snapshot assertions.
     */
    public function compiledQuery(): Builder
    {
        return $this->buildQuery();
    }
}
