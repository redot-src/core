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
