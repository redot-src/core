<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Redot\Datatables\Query\RelationSorter;
use Tests\Fixtures\Datatables\BlogAuthor;
use Tests\Fixtures\Datatables\BlogComment;
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
    BlogComment::query()->create(['post_id' => $testing->id, 'body' => 'Great insights']);
});

it('sorts by a single-level relation column using an aggregate', function () {
    $query = BlogComment::query();

    RelationSorter::apply($query, 'post.title', 'desc');

    expect($query->toSql())->toContain('order by "post_title" desc')
        ->and($query->pluck('id')->all())->toBe([2, 1]);
});

it('sorts by a nested relation column using a correlated subquery', function () {
    $query = BlogComment::query();

    RelationSorter::apply($query, 'post.author.name', 'desc');

    expect($query->toSql())->toContain('order by "post_author_name" desc')
        ->and($query->pluck('id')->all())->toBe([2, 1]);
});

it('preserves a previously selected column list when sorting by a nested relation', function () {
    $query = BlogComment::query()->select('blog_comments.id');

    RelationSorter::apply($query, 'post.author.name', 'asc');

    expect($query->pluck('id')->all())->toBe([1, 2]);
});
