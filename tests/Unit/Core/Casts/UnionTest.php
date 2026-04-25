<?php

use Illuminate\Database\Eloquent\Model;
use Redot\Casts\Union;

it('casts stored strings back to primitive and structured values', function (mixed $stored, mixed $expected) {
    $cast = new Union;

    expect($cast->get(new class extends Model {}, 'value', $stored, []))->toBe($expected);
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

    expect($cast->set(new class extends Model {}, 'value', $value, []))->toBe($expected);
})->with([
    [true, 'true'],
    [false, 'false'],
    [['primary' => 'blue'], '{"primary":"blue"}'],
    ['plain text', 'plain text'],
]);
