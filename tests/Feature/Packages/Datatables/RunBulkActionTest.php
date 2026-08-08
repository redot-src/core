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
        ->set('selected', [(string) $selected->getKey()])
        ->call('runBulkAction', 'approve')
        ->assertSet('selected', []);

    expect($selected->fresh()->approved)->toBeTrue()
        ->and($untouched->fresh()->approved)->toBeFalse();
});

it('renders a selection column and a bulk action bar once rows are selected', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->assertSeeHtml('wire:click="toggleSelection"')
        ->assertDontSeeHtml('wire:click="clearSelection"')
        ->set('selected', [(string) $post->getKey()])
        ->assertSeeHtml('wire:click="clearSelection"')
        ->assertSee('1 entry selected');
});

it('selects every row on the current page and deselects them on a second toggle', function () {
    $posts = collect(range(1, 3))->map(fn () => RunActionPost::query()->create(['approved' => false]));

    // The datatable defaults to sorting by the primary key descending.
    $keys = $posts->reverse()->map(fn ($post) => (string) $post->getKey())->values()->all();

    Livewire::test(BulkActionDatatable::class)
        ->call('toggleSelection')
        ->assertSet('selected', $keys)
        ->call('toggleSelection')
        ->assertSet('selected', []);
});

it('ignores selected keys that fall outside the datatable query', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->set('selected', [(string) $post->getKey(), '999'])
        ->call('runBulkAction', 'succeed')
        ->assertSet('successResult', 1);
});

it('throws when the bulk action name is unknown', function () {
    Livewire::test(BulkActionDatatable::class)
        ->set('selected', ['1'])
        ->call('runBulkAction', 'missing');
})->throws(InvalidActionException::class, 'Bulk action [missing] not found.');

it('throws when no rows are selected', function () {
    Livewire::test(BulkActionDatatable::class)
        ->call('runBulkAction', 'approve');
})->throws(InvalidActionException::class, 'Bulk action [approve] is not available for the selected rows.');

it('throws when the bulk action condition rejects the selection', function () {
    $first = RunActionPost::query()->create(['approved' => false]);
    $second = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->set('selected', [(string) $first->getKey(), (string) $second->getKey()])
        ->call('runBulkAction', 'limited');
})->throws(InvalidActionException::class, 'Bulk action [limited] is not available for the selected rows.');

it('refuses to run a hidden bulk action', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->set('selected', [(string) $post->getKey()])
        ->call('runBulkAction', 'hidden');
})->throws(InvalidActionException::class, 'Bulk action [hidden] is not available for the selected rows.');

it('fires the success callback after a successful bulk action', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->set('selected', [(string) $post->getKey()])
        ->call('runBulkAction', 'succeed')
        ->assertSet('successCallbackFired', true)
        ->assertSet('successResult', 1);
});

it('fires the failure callback when a bulk action throws and keeps the selection', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->set('selected', [(string) $post->getKey()])
        ->call('runBulkAction', 'fail')
        ->assertSet('failureCallbackFired', true)
        ->assertSet('failureExceptionMessage', 'Bulk action failed')
        ->assertSet('selected', [(string) $post->getKey()]);
});

it('rethrows bulk action exceptions when no failure callback is registered', function () {
    $post = RunActionPost::query()->create(['approved' => false]);

    Livewire::test(BulkActionDatatable::class)
        ->set('selected', [(string) $post->getKey()])
        ->call('runBulkAction', 'explode');
})->throws(RuntimeException::class, 'Unhandled bulk failure');
