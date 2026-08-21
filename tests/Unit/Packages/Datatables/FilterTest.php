<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Redot\Datatables\Filters\DateFilter;
use Redot\Datatables\Filters\NumberFilter;
use Redot\Datatables\Filters\SelectFilter;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;
use Tests\Fixtures\EmptyModel;

function datatableFilterModel(): EmptyModel
{
    return tap(new EmptyModel, function (EmptyModel $model): void {
        $model->setTable('datatable_filter_fixtures');
        $model->timestamps = false;
    });
}

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

    datatableFilterModel()->insert([
        ['name' => '0', 'score' => 0, 'status' => 'draft', 'active' => true, 'published_on' => null],
        ['name' => 'Alpha', 'score' => 10, 'status' => 'draft', 'active' => true, 'published_on' => '2026-01-01'],
        ['name' => 'Beta', 'score' => 20, 'status' => 'published', 'active' => false, 'published_on' => '2026-02-01'],
        ['name' => 'Gamma', 'score' => 30, 'status' => 'published', 'active' => null, 'published_on' => '2026-03-01'],
    ]);
});

it('applies string filters to query columns', function () {
    $query = datatableFilterModel()->newQuery();

    StringFilter::make('name')->apply($query, ['operator' => 'contains', 'value' => 'amm']);

    expect($query->pluck('name')->all())->toBe(['Gamma']);
});

it('applies number filters to query columns', function () {
    $query = datatableFilterModel()->newQuery();

    NumberFilter::make('score')->apply($query, ['operator' => 'greater_than_or_equals', 'value' => 20]);

    expect($query->pluck('name')->all())->toBe(['Beta', 'Gamma']);
});

it('applies string and number filters when the value is zero', function () {
    $string = datatableFilterModel()->newQuery();
    StringFilter::make('name')->apply($string, ['operator' => 'equals', 'value' => '0']);

    $number = datatableFilterModel()->newQuery();
    NumberFilter::make('score')->apply($number, ['operator' => 'equals', 'value' => '0']);

    expect($string->pluck('name')->all())->toBe(['0'])
        ->and($number->pluck('name')->all())->toBe(['0']);
});

it('applies select and ternary filters', function () {
    $select = datatableFilterModel()->newQuery();
    SelectFilter::make('status')->apply($select, 'published');

    $ternary = datatableFilterModel()->newQuery();
    TernaryFilter::make('active')->empty()->apply($ternary, 'empty');

    expect($select->pluck('name')->all())->toBe(['Beta', 'Gamma'])
        ->and($ternary->pluck('name')->all())->toBe(['Gamma']);
});

it('ignores invalid filter operators and array select values', function () {
    $string = datatableFilterModel()->newQuery();
    StringFilter::make('name')->apply($string, ['operator' => 'invalid', 'value' => 'Alpha']);

    $number = datatableFilterModel()->newQuery();
    NumberFilter::make('score')->apply($number, ['operator' => 'invalid', 'value' => 10]);

    $select = datatableFilterModel()->newQuery();
    SelectFilter::make('status')->apply($select, ['published']);

    expect($string->count())->toBe(4)
        ->and($number->count())->toBe(4)
        ->and($select->count())->toBe(4);
});

it('applies date filters with ranges', function () {
    $query = datatableFilterModel()->newQuery();

    DateFilter::make('published_on')->apply($query, [
        'from' => '2026-01-15',
        'to' => '2026-02-15',
    ]);

    expect($query->pluck('name')->all())->toBe(['Beta']);
});

it('includes datetime values throughout the end day of a date range', function () {
    datatableFilterModel()->insert([
        'name' => 'End day',
        'score' => 40,
        'status' => 'published',
        'active' => true,
        'published_on' => '2026-02-15 23:59:59',
    ]);

    $query = datatableFilterModel()->newQuery();

    DateFilter::make('published_on')->apply($query, [
        'from' => '2026-02-15',
        'to' => '2026-02-15',
    ]);

    expect($query->pluck('name')->all())->toBe(['End day']);
});

it('ignores non-scalar filter values from crafted requests', function () {
    $string = datatableFilterModel()->newQuery();
    StringFilter::make('name')->apply($string, ['operator' => 'contains', 'value' => ['crafted']]);

    $number = datatableFilterModel()->newQuery();
    NumberFilter::make('score')->apply($number, ['operator' => 'equals', 'value' => ['crafted']]);

    $date = datatableFilterModel()->newQuery();
    DateFilter::make('published_on')->apply($date, ['from' => ['crafted'], 'to' => ['crafted']]);

    expect($string->count())->toBe(4)
        ->and($number->count())->toBe(4)
        ->and($date->count())->toBe(4);
});

it('skips applyTo for null and empty string values', function () {
    $null = datatableFilterModel()->newQuery();
    StringFilter::make('name')->applyTo($null, null);

    $empty = datatableFilterModel()->newQuery();
    StringFilter::make('name')->applyTo($empty, '');

    expect($null->toSql())->not->toContain('where')
        ->and($empty->toSql())->not->toContain('where');
});

it('still applies applyTo for zero values', function () {
    $query = datatableFilterModel()->newQuery();

    SelectFilter::make('name')->applyTo($query, '0');

    expect($query->pluck('name')->all())->toBe(['0']);
});

it('prefers the query override over the default apply in applyTo', function () {
    $query = datatableFilterModel()->newQuery();

    StringFilter::make('name')
        ->query(fn ($query, $value) => $query->where('score', '>=', $value))
        ->applyTo($query, 20);

    expect($query->pluck('name')->all())->toBe(['Beta', 'Gamma']);
});
