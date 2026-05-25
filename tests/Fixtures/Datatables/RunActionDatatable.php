<?php

namespace Tests\Fixtures\Datatables;

use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\Column;
use Redot\Datatables\Datatable;
use RuntimeException;

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
