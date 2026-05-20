<?php

use Redot\Datatables\Columns\Column;
use Redot\Datatables\Columns\TextColumn;
use Tests\Fixtures\EmptyModel;

it('marks dotted column names as relationships', function () {
    expect(Column::make('author.name')->relationship)->toBeTrue()
        ->and(Column::make('email')->relationship)->toBeFalse();
});

it('escapes raw values by default to prevent html injection', function () {
    $row = new EmptyModel([
        'name' => '<strong>Taylor</strong>',
    ]);

    expect(Column::make('name')->get($row))->toBe('&lt;strong&gt;Taylor&lt;/strong&gt;');
});

it('preserves the original value when the column is explicitly marked as html', function () {
    $row = new EmptyModel([
        'name' => '<strong>Taylor</strong>',
    ]);

    expect(Column::make('name')->html()->get($row))->toBe('<strong>Taylor</strong>');
});

it('uses the configured default value for nameless columns that have no attribute to read', function () {
    $row = new EmptyModel;

    expect(Column::make()->default('static')->get($row))->toBe('static');
});

it('runs the supplied getter closure to transform the raw value before output', function () {
    $row = new EmptyModel([
        'email' => 'taylor@example.com',
    ]);

    $value = Column::make('email')
        ->getter(fn (string $email) => strtoupper($email))
        ->get($row);

    expect($value)->toBe('TAYLOR@EXAMPLE.COM');
});

it('returns the placeholder string when the resolved value is null', function () {
    $row = new EmptyModel;

    expect(Column::make('missing_attribute')->empty('—')->get($row))->toBe('—');
});

it('returns the actual unescaped value when requested explicitly', function () {
    $row = new EmptyModel([
        'name' => '<strong>Taylor</strong>',
    ]);

    expect(Column::make('name')->get($row, returnActualValue: true))->toBe('<strong>Taylor</strong>');
});

it('wraps emails into mailto anchors when the email modifier is set', function () {
    $row = new EmptyModel([
        'email' => 'taylor@example.com',
    ]);

    expect(TextColumn::make('email')->email()->get($row))
        ->toBe('<a href="mailto:taylor@example.com">taylor@example.com</a>');
});

it('truncates long text values to the configured character limit', function () {
    $row = new EmptyModel(['name' => str_repeat('a', 50)]);

    expect(TextColumn::make('name')->truncate(10)->get($row))->toBe('aaaaaaaaaa...');
});
