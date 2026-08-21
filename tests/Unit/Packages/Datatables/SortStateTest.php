<?php

use Redot\Datatables\SortState;

it('parses and formats the sort string round-trip', function () {
    $state = SortState::fromString('name:asc, created_at:desc');

    expect($state->all()->all())->toBe(['name' => 'asc', 'created_at' => 'desc'])
        ->and($state->toString())->toBe('name:asc,created_at:desc');
});

it('defaults missing or invalid directions to ascending', function () {
    $state = SortState::fromString('name,created_at:sideways');

    expect($state->all()->all())->toBe(['name' => 'asc', 'created_at' => 'asc']);
});

it('ignores empty segments', function () {
    expect(SortState::fromString('')->all()->isEmpty())->toBeTrue()
        ->and(SortState::fromString(',name:desc,')->all()->all())->toBe(['name' => 'desc']);
});

it('cycles a column asc, desc, then unsorted, replacing other sorts', function () {
    $state = SortState::empty()->cycle('name');
    expect($state->all()->all())->toBe(['name' => 'asc']);

    $state = $state->cycle('name');
    expect($state->all()->all())->toBe(['name' => 'desc']);

    $state = $state->cycle('name');
    expect($state->all()->isEmpty())->toBeTrue();

    $replaced = SortState::fromString('name:asc,email:desc')->cycle('email');
    expect($replaced->all()->all())->toBe(['email' => 'asc']);
});

it('cycles a column in place when appending, keeping other sorts', function () {
    $state = SortState::fromString('name:asc')->cycleAppend('email');
    expect($state->all()->all())->toBe(['name' => 'asc', 'email' => 'asc']);

    $state = $state->cycleAppend('email');
    expect($state->all()->all())->toBe(['name' => 'asc', 'email' => 'desc']);

    $state = $state->cycleAppend('email');
    expect($state->all()->all())->toBe(['name' => 'asc']);
});

it('is immutable', function () {
    $state = SortState::fromString('name:asc');
    $state->cycle('name');
    $state->cycleAppend('email');

    expect($state->toString())->toBe('name:asc');
});
