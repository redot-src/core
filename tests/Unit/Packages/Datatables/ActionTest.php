<?php

use Illuminate\Database\Eloquent\Model;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Tests\Fixtures\Core\EmptyModel;
use Tests\Fixtures\Datatables\DatatableActionRow;

it('configures actions and evaluates visibility conditions', function () {
    $row = new DatatableActionRow;

    $action = Action::make('Edit', 'edit-icon')
        ->href(fn (Model $row) => '/users/' . $row->getAttribute('id') . '/edit')
        ->method('post')
        ->body(['active' => fn (Model $row) => $row->getAttribute('active')])
        ->newTab()
        ->fancybox()
        ->confirmable(message: 'Continue?')
        ->condition(fn (Model $row) => $row->getAttribute('active'));

    $attributes = $action->buildAttributes($row)->getAttributes();

    expect($action->shouldRender($row))->toBeTrue()
        ->and($attributes['href'])->toBe('/users/5/edit')
        ->and($attributes['target'])->toBe('_blank')
        ->and($attributes['data-fancybox'])->toBe('')
        ->and($attributes['confirm'])->toBe('Continue?')
        ->and($attributes['class'])->toContain('datatable-action');
});

it('rejects invalid action methods and confirmable get actions', function () {
    Action::make()->method('trace');
})->throws(InvalidArgumentException::class, 'Invalid method provided "trace"');

it('requires confirmable actions to use non-get methods when attributes are built', function () {
    Action::make('Delete')->confirmable()->buildAttributes(new EmptyModel);
})->throws(InvalidArgumentException::class, 'Confirmable actions must have a method other than "get".');

it('groups actions and renders only when a child action can render', function () {
    $row = new DatatableActionRow(['id' => 7]);

    $visible = Action::make('Visible');
    $hidden = Action::make('Hidden')->hidden();
    $group = ActionGroup::make('More')->actions([$visible, $hidden]);

    expect($visible->grouped)->toBeTrue()
        ->and($hidden->grouped)->toBeTrue()
        ->and($group->shouldRender($row))->toBeTrue()
        ->and(ActionGroup::make('Empty')->actions([$hidden])->shouldRender($row))->toBeFalse();
});
