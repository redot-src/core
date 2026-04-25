<?php

use Illuminate\Database\Eloquent\Model;
use Redot\Datatables\Columns\Column;
use Redot\Datatables\Columns\TextColumn;

it('configures column state fluently', function () {
    $column = Column::make('author.name', 'Author')
        ->width('10rem', '8rem', '12rem')
        ->fixed(direction: 'end')
        ->sortable()
        ->searchable()
        ->hidden()
        ->exportable(false);

    expect($column->name)->toBe('author.name')
        ->and($column->relationship)->toBeTrue()
        ->and($column->label)->toBe('Author')
        ->and($column->width)->toBe('10rem')
        ->and($column->minWidth)->toBe('8rem')
        ->and($column->maxWidth)->toBe('12rem')
        ->and($column->fixed)->toBeTrue()
        ->and($column->fixedDirection)->toBe('end')
        ->and($column->sortable)->toBeTrue()
        ->and($column->searchable)->toBeTrue()
        ->and($column->visible)->toBeFalse()
        ->and($column->exportable)->toBeFalse();
});

it('escapes plain values and preserves html columns', function () {
    $row = new class extends Model
    {
        protected $attributes = [
            'name' => '<strong>Taylor</strong>',
            'email' => 'taylor@example.com',
        ];
    };

    expect(Column::make('name')->get($row))->toBe('&lt;strong&gt;Taylor&lt;/strong&gt;')
        ->and(TextColumn::make('email')->email()->get($row))
        ->toBe('<a href="mailto:taylor@example.com">taylor@example.com</a>');
});
