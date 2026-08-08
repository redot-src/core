<?php

use Illuminate\Support\Facades\Route;
use Redot\Datatables\Actions\BulkAction;

it('builds attributes flagged for bulk livewire execution', function () {
    $action = BulkAction::make('Approve', 'fas fa-check')
        ->action('approve', fn () => null)
        ->confirmable(message: 'Are you sure?');

    $attributes = $action->buildAttributes()->getAttributes();

    expect($attributes['href'])->toBe('#')
        ->and($attributes['action-name'])->toBe('approve')
        ->and($attributes['action-scope'])->toBe('bulk')
        ->and($attributes)->not->toHaveKey('action-key')
        ->and($attributes['confirm'])->toBe('Are you sure?');
});

it('exposes a delete factory that deletes the selected records', function () {
    $action = BulkAction::delete();

    expect($action->name)->toBe('delete')
        ->and($action->confirmable)->toBeTrue()
        ->and($action->callback)->not->toBeNull();
});

it('sends the current selection in the request body of route driven bulk actions', function () {
    Route::name('posts.bulk-destroy')->delete('/posts', fn () => 'destroy');

    $action = BulkAction::delete('posts.bulk-destroy')->selected(['1', '2']);

    $attributes = $action->buildAttributes()->getAttributes();
    $body = json_decode(base64_decode($attributes['request-body']), true);

    expect($action->callback)->toBeNull()
        ->and($attributes['method'])->toBe('delete')
        ->and($body)->toBe(['keys' => ['1', '2']]);
});

it('renames the request key holding the selection', function () {
    Route::name('posts.bulk-destroy')->delete('/posts', fn () => 'destroy');

    $action = BulkAction::delete('posts.bulk-destroy')->keys('ids')->selected(['3']);

    $body = json_decode(base64_decode($action->buildAttributes()->getAttributes()['request-body']), true);

    expect($body)->toBe(['ids' => ['3']]);
});

it('keeps the selection out of the action instance once attributes are built', function () {
    $action = BulkAction::make('Export', 'fas fa-file-export')->action('export', fn () => null);

    $action->buildAttributes();

    expect($action->attributes)->toBe([]);
});
