<?php

use Redot\Datatables\Columns\ColorColumn;
use Redot\Datatables\Columns\Column;
use Redot\Datatables\Columns\IconColumn;
use Redot\Datatables\Columns\NumericColumn;
use Redot\Datatables\Columns\TagsColumn;
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

    expect(Column::make('name')->get($row, raw: true))->toBe('<strong>Taylor</strong>');
});

it('returns the actual value before applying column display formatting', function () {
    $row = new EmptyModel([
        'amount' => 1234.5,
    ]);

    $column = NumericColumn::make('amount')->precision(2);

    expect($column->get($row))->toBe('1,234.50')
        ->and($column->get($row, raw: true))->toBe(1234.5);
});

it('wraps emails into mailto anchors when the email modifier is set', function () {
    $row = new EmptyModel([
        'email' => 'taylor@example.com',
    ]);

    expect(TextColumn::make('email')->email()->get($row))
        ->toBe('<a href="mailto:taylor@example.com">taylor@example.com</a>');
});

it('returns email text without the mailto anchor when the actual value is requested', function () {
    $row = new EmptyModel([
        'email' => 'taylor@example.com',
    ]);

    expect(TextColumn::make('email')->email()->get($row, raw: true))
        ->toBe('taylor@example.com');
});

it('truncates long text values to the configured character limit', function () {
    $row = new EmptyModel(['name' => str_repeat('a', 50)]);

    expect(TextColumn::make('name')->truncate(10)->get($row))->toBe('aaaaaaaaaa...');
});

it('escapes the value inside email anchors to prevent stored xss', function () {
    $row = new EmptyModel([
        'email' => '"><img src=x onerror=alert(1)>@x.com',
    ]);

    $value = TextColumn::make('email')->email()->get($row);

    expect($value)->not->toContain('<img')
        ->and($value)->toBe(sprintf(
            '<a href="mailto:%1$s">%1$s</a>',
            e('"><img src=x onerror=alert(1)>@x.com'),
        ));
});

it('escapes the value inside phone anchors to prevent stored xss', function () {
    $row = new EmptyModel([
        'phone' => '<script>alert(1)</script>',
    ]);

    expect(TextColumn::make('phone')->phone()->get($row))->not->toContain('<script>');
});

it('escapes the value inside url anchors to prevent stored xss', function () {
    $row = new EmptyModel([
        'website' => 'https://example.com/"><script>alert(1)</script>',
    ]);

    $value = TextColumn::make('website')->url()->get($row);

    expect($value)->not->toContain('<script>')
        ->and($value)->toContain('href="https://example.com/&quot;&gt;');
});

it('refuses to link urls with unsafe schemes like javascript', function () {
    $row = new EmptyModel(['website' => 'javascript:alert(1)']);

    expect(TextColumn::make('website')->url()->get($row))->toBe(e('javascript:alert(1)'));
});

it('refuses to link urls whose scheme hides behind control characters', function () {
    $row = new EmptyModel(['website' => "java\tscript:alert(1)"]);

    expect(TextColumn::make('website')->url()->get($row))->not->toContain('<a ');
});

it('still links plain http and https urls', function () {
    $row = new EmptyModel(['website' => 'https://example.com']);

    expect(TextColumn::make('website')->url()->get($row))
        ->toBe('<a href="https://example.com" target="_self">https://example.com</a>');
});

it('escapes the value inside icon column classes to prevent stored xss', function () {
    $row = new EmptyModel(['icon' => '"><script>alert(1)</script>']);

    expect(IconColumn::make('icon')->get($row))->not->toContain('<script>');
});

it('drops color values that would inject extra css declarations', function () {
    $row = new EmptyModel(['color' => 'red;position:fixed;inset:0']);

    $style = ColorColumn::make('color')->buildAttributes($row)->get('style');

    expect($style)->not->toContain('position')
        ->and($style)->not->toContain('background-color');
});

it('accepts plain css colors for the color column swatch', function () {
    $row = new EmptyModel(['color' => '#ff0000']);

    expect(ColorColumn::make('color')->buildAttributes($row)->get('style'))
        ->toContain('background-color: #ff0000');

    $row = new EmptyModel(['color' => 'rgb(255, 0, 0)']);

    expect(ColorColumn::make('color')->buildAttributes($row)->get('style'))
        ->toContain('background-color: rgb(255, 0, 0)');

    $row = new EmptyModel(['color' => 'rebeccapurple']);

    expect(ColorColumn::make('color')->buildAttributes($row)->get('style'))
        ->toContain('background-color: rebeccapurple');

    $row = new EmptyModel(['color' => 'var(--brand)']);

    expect(ColorColumn::make('color')->buildAttributes($row)->get('style'))
        ->toContain('background-color: var(--brand)');

    $row = new EmptyModel(['color' => 'color-mix(in srgb, red 50%, rgb(0, 0, 255))']);

    expect(ColorColumn::make('color')->buildAttributes($row)->get('style'))
        ->toContain('background-color: color-mix(in srgb, red 50%, rgb(0, 0, 255))');
});

it('escapes tag values to prevent stored xss', function () {
    $row = new EmptyModel(['tags' => ['<script>alert(1)</script>', 'safe']]);

    $value = TagsColumn::make('tags')->get($row);

    expect($value)->not->toContain('<script>alert')
        ->and($value)->toContain('<span class="tag">safe</span>');
});
