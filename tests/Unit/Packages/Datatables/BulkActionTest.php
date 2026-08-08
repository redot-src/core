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

it('names the request key carrying the selection on route driven bulk actions', function () {
    Route::name('posts.bulk-destroy')->delete('/posts', fn () => 'destroy');

    $action = BulkAction::delete('posts.bulk-destroy');

    $attributes = $action->buildAttributes()->getAttributes();

    expect($action->callback)->toBeNull()
        ->and($attributes['method'])->toBe('delete')
        ->and($attributes['bulk-keys'])->toBe('keys')
        ->and(json_decode(base64_decode($attributes['request-body']), true))->toBe([]);
});

it('marks a get bulk action so the selection can be appended to the query string', function () {
    Route::name('posts.bulk-export')->get('/posts/export', fn () => 'export');

    $attributes = BulkAction::make('Export', 'fas fa-file-export')
        ->route('posts.bulk-export')
        ->keys('ids')
        ->buildAttributes()
        ->getAttributes();

    expect($attributes['method'])->toBe('get')
        ->and($attributes['bulk-keys'])->toBe('ids')
        ->and($attributes['href'])->toContain('/posts/export');
});

it('renames the request key holding the selection', function () {
    Route::name('posts.bulk-destroy')->delete('/posts', fn () => 'destroy');

    $action = BulkAction::delete('posts.bulk-destroy')->keys('ids');

    expect($action->buildAttributes()->getAttributes()['bulk-keys'])->toBe('ids');
});

it('keeps the selection out of the action instance once attributes are built', function () {
    $action = BulkAction::make('Export', 'fas fa-file-export')->action('export', fn () => null);

    $action->buildAttributes();

    expect($action->attributes)->toBe([]);
});
