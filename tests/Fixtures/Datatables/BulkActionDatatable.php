<?php

namespace Tests\Fixtures\Datatables;

use Illuminate\Database\Eloquent\Collection;
use Redot\Datatables\Actions\BulkAction;
use Redot\Datatables\Columns\Column;
use Redot\Datatables\Datatable;
use RuntimeException;

class BulkActionDatatable extends Datatable
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

    public function bulkActions(): array
    {
        return [
            BulkAction::make('Approve', 'ti ti-check')
                ->action('approve', fn (Collection $records) => $records->each->update(['approved' => true])),
            BulkAction::make('Limited', 'ti ti-lock')
                ->action('limited', fn (Collection $records) => $records->each->delete())
                ->condition(fn (Collection $records) => $records->count() < 2),
            BulkAction::make('Hidden', 'ti ti-eye-off')
                ->action('hidden', fn (Collection $records) => $records->each->delete())
                ->hidden(),
            BulkAction::make('Fail', 'ti ti-x')
                ->action('fail', fn () => throw new RuntimeException('Bulk action failed'))
                ->failure(function ($records, $exception, BulkActionDatatable $datatable) {
                    $datatable->failureCallbackFired = true;
                    $datatable->failureExceptionMessage = $exception->getMessage();
                }),
            BulkAction::make('Succeed', 'ti ti-checks')
                ->action('succeed', fn (Collection $records) => $records->count())
                ->success(function ($records, $result, BulkActionDatatable $datatable) {
                    $datatable->successCallbackFired = true;
                    $datatable->successResult = $result;
                }),
            BulkAction::make('Explode', 'ti ti-bomb')
                ->action('explode', fn () => throw new RuntimeException('Unhandled bulk failure')),
        ];
    }
}
