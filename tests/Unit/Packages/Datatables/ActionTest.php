<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Tests\Fixtures\Core\EmptyModel;
use Tests\Fixtures\Datatables\DatatableActionRow;

it('rejects unknown http methods at configuration time', function () {
    Action::make()->method('trace');
})->throws(InvalidArgumentException::class, 'Invalid method provided "trace"');

it('rejects confirmable get actions when attributes are built', function () {
    Action::make('Delete')->confirmable()->buildAttributes(new EmptyModel);
})->throws(InvalidArgumentException::class, 'Confirmable actions must have a method other than "get".');

it('uses an explicit href closure to compute the action url per row', function () {
    $action = Action::make('Edit')->href(fn (Model $row) => '/users/' . $row->getAttribute('id') . '/edit');

    $attributes = $action->buildAttributes(new DatatableActionRow)->getAttributes();

    expect($attributes['href'])->toBe('/users/5/edit');
});

it('resolves the href and method from a named route binding the row as the first parameter', function () {
    Route::name('admins.edit')->get('/admins/{user}/edit', fn () => 'edit');

    $action = Action::make('Edit')->route('admins.edit')->method('post');

    $attributes = $action->buildAttributes(new DatatableActionRow(['id' => 12]))->getAttributes();

    expect($attributes['href'])->toContain('/admins/12/edit')
        ->and($attributes['method'])->toBe('post')
        ->and($attributes)->toHaveKey('token');
});

it('opens links in a new tab when newTab is enabled', function () {
    $action = Action::make('Open')->href('/anywhere')->newTab();

    expect($action->buildAttributes(new EmptyModel)->getAttributes())
        ->toMatchArray(['target' => '_blank']);
});

it('renders only when the visible flag is set and the condition callback returns true', function () {
    $row = new DatatableActionRow(['active' => false]);

    $visible = Action::make('Edit');
    $conditionFails = Action::make('Edit')->condition(fn ($row) => $row->getAttribute('active'));
    $hidden = Action::make('Edit')->hidden();

    expect($visible->shouldRender($row))->toBeTrue()
        ->and($conditionFails->shouldRender($row))->toBeFalse()
        ->and($hidden->shouldRender($row))->toBeFalse();
});

it('exposes a delete factory that defaults to a confirmable delete request', function () {
    Route::name('admins.destroy')->delete('/admins/{user}', fn () => 'destroy');

    $action = Action::delete('admins.destroy');

    expect($action->method)->toBe('delete')
        ->and($action->confirmable)->toBeTrue();

    $attributes = $action->buildAttributes(new DatatableActionRow(['id' => 7]))->getAttributes();

    expect($attributes['method'])->toBe('delete')
        ->and($attributes)->toHaveKey('confirm');
});

it('flags grouped actions and renders the group only when at least one child can render', function () {
    $row = new DatatableActionRow(['id' => 1]);

    $visible = Action::make('Visible');
    $hidden = Action::make('Hidden')->hidden();

    $populatedGroup = ActionGroup::make('More')->actions([$visible, $hidden]);
    $emptyGroup = ActionGroup::make('Hidden Only')->actions([Action::make('Hidden Too')->hidden()]);

    expect($visible->grouped)->toBeTrue()
        ->and($hidden->grouped)->toBeTrue()
        ->and($populatedGroup->shouldRender($row))->toBeTrue()
        ->and($emptyGroup->shouldRender($row))->toBeFalse();
});
