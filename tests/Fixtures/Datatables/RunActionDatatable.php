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
            Action::make('Approve', 'ti ti-check')
                ->action('approve', fn (RunActionPost $row) => $row->update(['approved' => true]))
                ->condition(fn (RunActionPost $row) => ! $row->approved),
            Action::make('Fail', 'ti ti-x')
                ->action('fail', fn () => throw new RuntimeException('Action failed'))
                ->success(fn () => null)
                ->failure(function ($row, $exception, RunActionDatatable $datatable) {
                    $datatable->failureCallbackFired = true;
                    $datatable->failureExceptionMessage = $exception->getMessage();
                }),
            Action::make('Succeed', 'ti ti-checks')
                ->action('succeed', fn (RunActionPost $row) => 'done')
                ->success(function ($row, $result, RunActionDatatable $datatable) {
                    $datatable->successCallbackFired = true;
                    $datatable->successResult = $result;
                }),
            Action::make('Explode', 'ti ti-bomb')
                ->action('explode', fn () => throw new RuntimeException('Unhandled failure')),
            ActionGroup::make('More', 'ti ti-dots-vertical')
                ->condition(fn (RunActionPost $row) => $row->approved)
                ->actions([
                    Action::make('Archive', 'ti ti-archive')
                        ->action('archive', fn (RunActionPost $row) => $row->update(['approved' => false])),
                ]),
        ];
    }
}
