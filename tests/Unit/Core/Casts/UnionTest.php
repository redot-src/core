<?php

use Redot\Casts\Union;
use Tests\Fixtures\Core\EmptyModel;

it('casts stored strings back to primitive and structured values', function (mixed $stored, mixed $expected) {
    $cast = new Union;

    expect($cast->get(new EmptyModel, 'value', $stored, []))->toBe($expected);
})->with([
    ['true', true],
    ['false', false],
    ['15', 15],
    ['{"primary":"blue"}', ['primary' => 'blue']],
    ['["en","ar"]', ['en', 'ar']],
    ['plain text', 'plain text'],
]);

it('prepares booleans and arrays for storage', function (mixed $value, mixed $expected) {
    $cast = new Union;

    expect($cast->set(new EmptyModel, 'value', $value, []))->toBe($expected);
})->with([
    [true, 'true'],
    [false, 'false'],
    [['primary' => 'blue'], '{"primary":"blue"}'],
    ['plain text', 'plain text'],
]);
