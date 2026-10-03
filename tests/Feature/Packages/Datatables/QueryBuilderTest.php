<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Datatables\BlogAuthor;
use Tests\Fixtures\Datatables\BlogComment;
use Tests\Fixtures\Datatables\BlogCommentsDatatable;
use Tests\Fixtures\Datatables\BlogPost;

beforeEach(function () {
    Schema::create('blog_authors', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('blog_posts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('author_id');
        $table->string('title');
    });

    Schema::create('blog_comments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('post_id');
        $table->string('body');
    });

    $amy = BlogAuthor::query()->create(['name' => 'Amy']);
    $bob = BlogAuthor::query()->create(['name' => 'Bob']);

    $laravel = BlogPost::query()->create(['author_id' => $amy->id, 'title' => 'Laravel Tips']);
    $testing = BlogPost::query()->create(['author_id' => $bob->id, 'title' => 'Testing Guide']);

    BlogComment::query()->create(['post_id' => $laravel->id, 'body' => 'Great post']);
    BlogComment::query()->create(['post_id' => $laravel->id, 'body' => 'Nice work']);
    BlogComment::query()->create(['post_id' => $testing->id, 'body' => 'Great insights']);
});

it('sorts by the primary key descending when no sort is active', function () {
    $datatable = new BlogCommentsDatatable;

    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([3, 2, 1]);
});

it('searches direct and relation columns globally', function () {
    $datatable = new BlogCommentsDatatable;
    $datatable->search = 'Great';

    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([3, 1]);

    $datatable->search = 'Laravel';

    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([2, 1]);
});

it('searches for zero in direct and relation columns while allowing an empty search', function () {
    BlogComment::query()->findOrFail(1)->update(['body' => '0']);
    BlogPost::query()->findOrFail(2)->update(['title' => 'Version 0']);

    $datatable = new BlogCommentsDatatable;
    $datatable->search = '0';

    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([3, 1]);

    $datatable->search = '';

    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([3, 2, 1]);
});

it('sorts by a single-level relation column', function () {
    $datatable = new BlogCommentsDatatable;
    $datatable->sortColumn = '-post.title';

    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([3, 1, 2]);
});

it('sorts by a nested relation column', function () {
    $datatable = new BlogCommentsDatatable;
    $datatable->sortColumn = '-post.author.name,body';

    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([3, 1, 2]);
});

it('applies nested and global filters', function () {
    $datatable = new BlogCommentsDatatable;
    [$body, $title] = $datatable->filters();

    $datatable->filtered = [
        $body->index => ['operator' => 'contains', 'value' => 'Great'],
        $title->index => 'Laravel Tips',
    ];

    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([1]);
});

it('produces the same SQL as before the refactor for a fully loaded query', function () {
    $datatable = new BlogCommentsDatatable;
    [$body, $title] = $datatable->filters();

    $datatable->search = 'Great';
    $datatable->sortColumn = '-post.author.name,body';
    $datatable->filtered = [
        $body->index => ['operator' => 'contains', 'value' => 'Great'],
        $title->index => 'Laravel Tips',
    ];

    $query = $datatable->compiledQuery();

    expect($query->toSql())->toBe(
        'select "blog_comments".*, (select (select "blog_authors"."name" from "blog_authors" where "blog_posts"."author_id" = "blog_authors"."id" limit 1) as "nested_relation_value" from "blog_posts" where "blog_comments"."post_id" = "blog_posts"."id" limit 1) as "post_author_name" from "blog_comments" where (exists (select * from "blog_posts" where "blog_comments"."post_id" = "blog_posts"."id" and "title" = ?)) and (("body" like ?)) and ("body" like ? or exists (select * from "blog_posts" where "blog_comments"."post_id" = "blog_posts"."id" and "title" like ?)) order by "post_author_name" desc, "body" asc'
    )->and($query->getBindings())->toBe(['Laravel Tips', '%Great%', '%Great%', '%Great%']);
});

it('ignores client-supplied sorts for unknown or non-sortable columns', function () {
    $datatable = new BlogCommentsDatatable;

    $datatable->sortColumn = 'password';
    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([3, 2, 1]);

    // The id column exists but is not sortable.
    $datatable->sortColumn = 'id';
    expect($datatable->compiledQuery()->pluck('id')->all())->toBe([3, 2, 1]);
});

it('cycles sorts through the livewire sort endpoint', function () {
    Livewire\Livewire::test(BlogCommentsDatatable::class)
        ->call('sort', 'body')->assertSet('sortColumn', 'body')
        ->call('sort', 'body')->assertSet('sortColumn', '-body')
        ->call('sort', 'body')->assertSet('sortColumn', '')
        ->call('sort', 'body')->assertSet('sortColumn', 'body')
        ->call('sort', 'post.title', true)->assertSet('sortColumn', 'body,post.title')
        ->call('sort', 'post.title', true)->assertSet('sortColumn', 'body,-post.title')
        ->call('sort', 'post.title', true)->assertSet('sortColumn', 'body')
        ->call('sort')->assertSet('sortColumn', '');
});

describe('pagination updates', function () {
    beforeEach(function () {
        for ($i = 0; $i < 22; $i++) {
            BlogComment::query()->create(['post_id' => 1, 'body' => 'Extra comment']);
        }
    });

    it('returns to the first page when search changes or clears', function (string $search, array $expectedIds) {
        Livewire\Livewire::test(BlogCommentsDatatable::class)
            ->set('search', 'Extra')
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->assertViewHas('rows', fn ($rows) => $rows->currentPage() === 2 && $rows->count() === 10)
            ->set('search', $search)
            ->assertSet('paginators.page', 1)
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === $expectedIds);
    })->with([
        'narrow search' => ['Nice work', [2]],
        'clear search' => ['', range(25, 16)],
    ]);

    it('returns to the first page when the filter array changes', function () {
        [$body] = (new BlogCommentsDatatable)->filters();

        Livewire\Livewire::test(BlogCommentsDatatable::class)
            ->call('gotoPage', 2)
            ->set('filtered', [$body->index => ['operator' => 'equals', 'value' => 'Nice work']])
            ->assertSet('paginators.page', 1)
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [2]);
    });

    it('returns to the first page when a nested filter value or operator changes', function (string $property, string $value, array $expectedIds) {
        [$body] = (new BlogCommentsDatatable)->filters();

        Livewire\Livewire::test(BlogCommentsDatatable::class)
            ->set('filtered', [$body->index => ['operator' => 'contains', 'value' => 'Extra']])
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set("filtered.{$body->index}.$property", $value)
            ->assertSet('paginators.page', 1)
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === $expectedIds);
    })->with([
        'value' => ['value', 'Nice', [2]],
        'operator' => ['operator', 'not_contains', [3, 2, 1]],
    ]);

    it('returns to the first page when all filters are cleared', function () {
        [$body] = (new BlogCommentsDatatable)->filters();

        Livewire\Livewire::test(BlogCommentsDatatable::class)
            ->set('filtered', [$body->index => ['operator' => 'contains', 'value' => 'Extra']])
            ->call('gotoPage', 2)
            ->set('filtered', [])
            ->assertSet('paginators.page', 1)
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === range(25, 16));
    });

    it('returns to the first page when the page size changes', function () {
        Livewire\Livewire::test(BlogCommentsDatatable::class)
            ->call('gotoPage', 3)
            ->assertViewHas('rows', fn ($rows) => $rows->currentPage() === 3 && $rows->count() === 5)
            ->set('perPage', 25)
            ->assertSet('paginators.page', 1)
            ->assertViewHas('rows', fn ($rows) => $rows->currentPage() === 1 && $rows->count() === 25);
    });
});
