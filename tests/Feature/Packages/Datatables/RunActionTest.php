<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\Column;
use Redot\Datatables\Datatable;
use Redot\Datatables\Exceptions\InvalidActionException;

class RunActionPost extends Model
{
    protected $table = 'posts';

    protected $guarded = [];

    protected $casts = [
        'approved' => 'boolean',
    ];
}

class RunActionDatatable extends Datatable
{
    protected string $model = RunActionPost::class;

    public bool $successCallbackFired = false;

    public bool $failureCallbackFired = false;

    public mixed $successResult = null;

    public ?string $failureExceptionMessage = null;

    public function columns(): array
    {
        return [
            Column::make('id'),
            Column::make('approved'),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('Approve', 'fas fa-check')
                ->action('approve', fn (RunActionPost $row) => $row->update(['approved' => true]))
                ->condition(fn (RunActionPost $row) => ! $row->approved),
            Action::make('Fail', 'fas fa-times')
                ->action('fail', fn () => throw new RuntimeException('Action failed'))
                ->success(fn () => null)
                ->failure(function ($row, $exception, RunActionDatatable $datatable) {
                    $datatable->failureCallbackFired = true;
                    $datatable->failureExceptionMessage = $exception->getMessage();
                }),
            Action::make('Succeed', 'fas fa-check-double')
                ->action('succeed', fn (RunActionPost $row) => 'done')
                ->success(function ($row, $result, RunActionDatatable $datatable) {
                    $datatable->successCallbackFired = true;
                    $datatable->successResult = $result;
                }),
            Action::make('Explode', 'fas fa-bomb')
                ->action('explode', fn () => throw new RuntimeException('Unhandled failure')),
            ActionGroup::make('More', 'fas fa-ellipsis-v')->actions([
                Action::make('Archive', 'fas fa-archive')
                    ->action('archive', fn (RunActionPost $row) => $row->update(['approved' => false])),
            ]),
        ];
    }
}

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
