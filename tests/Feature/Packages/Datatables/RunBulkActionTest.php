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
        $table->timestamps();
    });
});

it('runs a bulk action against the selected rows and clears the selection', function () {
    $selected = RunActionPost::query()->create(['approved' => false]);
    $untouched = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'approve', [(string) $selected->getKey()])
        ->assertJs('selected = []');

    expect($selected->fresh()->approved)->toBeTrue()
        ->and($untouched->fresh()->approved)->toBeFalse();
});

it('renders a selection column and a bulk action dropdown driven by the browser', function () {
    RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->assertSeeHtml('x-model="selected"')
        ->assertSeeHtml('<div class="dropdown datatable-bulk-actions" x-show="selected.length > 0"')
        ->assertSeeHtml('x-text="selectedLabel(')
        ->assertSeeHtml('class="datatable-action dropdown-item"');
});

it('never round trips the selection to the server', function () {
    RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->assertDontSeeHtml('wire:model.live="selected"')
        ->assertDontSeeHtml('wire:click="toggleSelection"');
});

it('ignores selected keys that fall outside the datatable query', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'succeed', [(string) $post->getKey(), '999'])
        ->assertSet('successResult', 1);
});

it('throws when the bulk action name is unknown', function () {
    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'missing', ['1']);
})->throws(InvalidActionException::class, 'Bulk action [missing] not found.');

it('throws when no rows are selected', function () {
    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'approve', []);
})->throws(InvalidActionException::class, 'Bulk action [approve] is not available for the selected rows.');

it('throws when the bulk action condition rejects the selection', function () {
    $first = RunActionPost::query()->create(['approved' => false]);
    $second = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'limited', [(string) $first->getKey(), (string) $second->getKey()]);
})->throws(InvalidActionException::class, 'Bulk action [limited] is not available for the selected rows.');

it('refuses to run a hidden bulk action', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'hidden', [(string) $post->getKey()]);
})->throws(InvalidActionException::class, 'Bulk action [hidden] is not available for the selected rows.');

it('fires the success callback after a successful bulk action', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'succeed', [(string) $post->getKey()])
        ->assertSet('successCallbackFired', true)
        ->assertSet('successResult', 1);
});

it('fires the failure callback when a bulk action throws and keeps the selection', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'fail', [(string) $post->getKey()])
        ->assertSet('failureCallbackFired', true)
        ->assertSet('failureExceptionMessage', 'Bulk action failed')
        ->assertNoJs();
});

it('rethrows bulk action exceptions when no failure callback is registered', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'explode', [(string) $post->getKey()]);
})->throws(RuntimeException::class, 'Unhandled bulk failure');
