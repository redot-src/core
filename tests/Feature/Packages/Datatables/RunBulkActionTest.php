<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Redot\Datatables\Exceptions\InvalidActionException;
use Tests\Fixtures\Datatables\BulkActionDatatable;
use Tests\Fixtures\Datatables\RunActionPost;

beforeEach(function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->boolean('approved')->default(false);
        $table->string('title')->nullable();
        $table->timestamps();
    });
});

it('runs a bulk action against the selected rows and clears the selection', function () {
    $first = RunActionPost::query()->create(['approved' => false]);
    $second = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'approve', [$first->getKey(), $second->getKey()])
        ->assertSet('selectionCleared', true);

    expect($first->fresh()->approved)->toBeTrue()
        ->and($second->fresh()->approved)->toBeTrue();
});

it('throws when the bulk action name is unknown', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'missing', [$post->getKey()]);
})->throws(InvalidActionException::class, 'Bulk action [missing] not found.');

it('throws when the selection is empty or matches no rows', function (array $keys) {
    RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'approve', $keys);
})->with([
    'no keys' => [[]],
    'unknown keys' => [[999]],
])->throws(InvalidActionException::class, 'Bulk action [approve] is not available for the selected rows.');

it('drops non-scalar keys from the selection', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'approve', [['nested' => 'array'], $post->getKey()]);

    expect($post->fresh()->approved)->toBeTrue();
});

it('deduplicates keys before the records reach the action', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    // The "limited" action only renders for fewer than two records, so a
    // duplicated key must collapse to a single record for it to run.
    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'limited', [$post->getKey(), $post->getKey(), (string) $post->getKey()]);

    expect(RunActionPost::query()->whereKey($post->getKey())->exists())->toBeFalse();
});

it('rejects hidden bulk actions', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'hidden', [$post->getKey()]);
})->throws(InvalidActionException::class, 'Bulk action [hidden] is not available for the selected rows.');

it('rejects bulk actions whose condition fails for the selection', function () {
    $first = RunActionPost::query()->create(['approved' => false]);
    $second = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'limited', [$first->getKey(), $second->getKey()]);
})->throws(InvalidActionException::class, 'Bulk action [limited] is not available for the selected rows.');

it('fires the success callback after a successful bulk action', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'succeed', [$post->getKey()])
        ->assertSet('successCallbackFired', true)
        ->assertSet('successResult', 1)
        ->assertSet('selectionCleared', true);
});

it('fires the failure callback without clearing the selection when a bulk action throws', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'fail', [$post->getKey()])
        ->assertSet('failureCallbackFired', true)
        ->assertSet('failureExceptionMessage', 'Bulk action failed')
        ->assertSet('selectionCleared', false);
});

it('rethrows bulk action exceptions when no failure callback is registered', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'explode', [$post->getKey()]);
})->throws(RuntimeException::class, 'Unhandled bulk failure');
