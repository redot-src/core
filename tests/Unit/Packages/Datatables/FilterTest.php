<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Redot\Datatables\Filters\DateFilter;
use Redot\Datatables\Filters\NumberFilter;
use Redot\Datatables\Filters\SelectFilter;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;
use Tests\Fixtures\Datatables\DatatableFilterFixture;

beforeEach(function () {
    Schema::dropIfExists('datatable_filter_fixtures');
    Schema::create('datatable_filter_fixtures', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('score');
        $table->string('status');
        $table->boolean('active')->nullable();
        $table->date('published_on')->nullable();
    });

    DatatableFilterFixture::insert([
        ['name' => 'Alpha', 'score' => 10, 'status' => 'draft', 'active' => true, 'published_on' => '2026-01-01'],
        ['name' => 'Beta', 'score' => 20, 'status' => 'published', 'active' => false, 'published_on' => '2026-02-01'],
        ['name' => 'Gamma', 'score' => 30, 'status' => 'published', 'active' => null, 'published_on' => '2026-03-01'],
    ]);
});

it('applies string filters to query columns', function () {
    $query = DatatableFilterFixture::query();

    StringFilter::make('name')->apply($query, ['operator' => 'contains', 'value' => 'amm']);

    expect($query->pluck('name')->all())->toBe(['Gamma']);
});

it('applies number filters to query columns', function () {
    $query = DatatableFilterFixture::query();

    NumberFilter::make('score')->apply($query, ['operator' => 'greater_than_or_equals', 'value' => 20]);

    expect($query->pluck('name')->all())->toBe(['Beta', 'Gamma']);
});

it('applies select and ternary filters', function () {
    $select = DatatableFilterFixture::query();
    SelectFilter::make('status')->apply($select, 'published');

    $ternary = DatatableFilterFixture::query();
    TernaryFilter::make('active')->empty()->apply($ternary, 'empty');

    expect($select->pluck('name')->all())->toBe(['Beta', 'Gamma'])
        ->and($ternary->pluck('name')->all())->toBe(['Gamma']);
});

it('applies date filters with ranges', function () {
    $query = DatatableFilterFixture::query();

    DateFilter::make('published_on')->apply($query, [
        'from' => '2026-01-15',
        'to' => '2026-02-15',
    ]);

    expect($query->pluck('name')->all())->toBe(['Beta']);
});
