<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Redot\Datatables\Exceptions\InvalidActionException;
use Tests\Fixtures\Datatables\RunActionDatatable;
use Tests\Fixtures\Datatables\RunActionPost;

beforeEach(function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->boolean('approved')->default(false);
        $table->timestamps();
    });
});

it('runs an inline action against a row and refreshes the datatable data', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(RunActionDatatable::class)
        ->call('runAction', 'approve', $post->getKey())
        ->assertSet('search', '');

    expect($post->fresh()->approved)->toBeTrue();
});

it('runs inline actions nested inside action groups', function () {
    $post = RunActionPost::query()->create(['approved' => true]);

    Livewire::test(RunActionDatatable::class)
        ->call('runAction', 'archive', $post->getKey());

    expect($post->fresh()->approved)->toBeFalse();
});

it('throws when the inline action name is unknown', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(RunActionDatatable::class)
        ->call('runAction', 'missing', $post->getKey());
})->throws(InvalidActionException::class, 'Action [missing] not found.');

it('throws when the inline action is not available for the row', function () {
    $post = RunActionPost::query()->create(['approved' => true]);

    Livewire::test(RunActionDatatable::class)
        ->call('runAction', 'approve', $post->getKey());
})->throws(InvalidActionException::class, 'Action [approve] is not available for this row.');

it('fires the success callback after a successful inline action', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(RunActionDatatable::class)
        ->call('runAction', 'succeed', $post->getKey())
        ->assertSet('successCallbackFired', true)
        ->assertSet('successResult', 'done');
});

it('fires the failure callback when an inline action throws and suppresses the exception', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(RunActionDatatable::class)
        ->call('runAction', 'fail', $post->getKey())
        ->assertSet('failureCallbackFired', true)
        ->assertSet('failureExceptionMessage', 'Action failed');
});

it('rethrows inline action exceptions when no failure callback is registered', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(RunActionDatatable::class)
        ->call('runAction', 'explode', $post->getKey());
})->throws(RuntimeException::class, 'Unhandled failure');
