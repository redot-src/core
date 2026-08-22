<?php

use Redot\Datatables\SortState;

it('parses and formats the sort string round-trip', function () {
    $state = SortState::fromString('name, -created_at');

    expect($state->all()->all())->toBe(['name' => 'asc', 'created_at' => 'desc'])
        ->and($state->toString())->toBe('name,-created_at');
});

it('treats a bare column as ascending', function () {
    expect(SortState::fromString('name')->all()->all())->toBe(['name' => 'asc'])
        ->and(SortState::fromString('name')->toString())->toBe('name');
});

it('ignores empty segments', function () {
    expect(SortState::fromString('')->all()->isEmpty())->toBeTrue()
        ->and(SortState::fromString(',-name,')->all()->all())->toBe(['name' => 'desc'])
        ->and(SortState::fromString('-')->all()->isEmpty())->toBeTrue();
});

it('cycles a column asc, desc, then unsorted, replacing other sorts', function () {
    $state = SortState::empty()->cycle('name');
    expect($state->all()->all())->toBe(['name' => 'asc']);

    $state = $state->cycle('name');
    expect($state->all()->all())->toBe(['name' => 'desc']);

    $state = $state->cycle('name');
    expect($state->all()->isEmpty())->toBeTrue();

    $replaced = SortState::fromString('name,-email')->cycle('email');
    expect($replaced->all()->all())->toBe(['email' => 'asc']);
});

it('cycles a column in place when appending, keeping other sorts', function () {
    $state = SortState::fromString('name')->cycleAppend('email');
    expect($state->all()->all())->toBe(['name' => 'asc', 'email' => 'asc']);

    $state = $state->cycleAppend('email');
    expect($state->all()->all())->toBe(['name' => 'asc', 'email' => 'desc']);

    $state = $state->cycleAppend('email');
    expect($state->all()->all())->toBe(['name' => 'asc']);
});

it('is immutable', function () {
    $state = SortState::fromString('name');
    $state->cycle('name');
    $state->cycleAppend('email');

    expect($state->toString())->toBe('name');
});
