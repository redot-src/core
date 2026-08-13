<?php

namespace Redot\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Actions\BulkAction;
use Redot\Datatables\Adapters\PDF\Adapter;
use Redot\Datatables\Columns\Column;
use Redot\Datatables\Filters\Filter;
use Redot\Toastify\Concerns\InteractsWithToastify;
use Redot\Traits\InteractsWithRelations;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

abstract class Datatable extends Component
{
    use InteractsWithRelations;
    use InteractsWithToastify;
    use Macroable;
    use WithPagination;

    /**
     * Unique identifier for the datatable.
     */
    #[Locked]
    public string $id;

    /**
     * Model bound to the datatable.
     */
    protected string $model;

    /**
     * The default per page options.
     */
    #[Locked]
    public array $perPageOptions = [5, 10, 25, 50, 100, 250, 500];

    /**
     * The default per page value.
     */
    #[Url]
    public int $perPage = 10;

    /**
     * The per page value declared by the datatable class, accepted
     * even when it is not listed in the per page options.
     */
    protected int $defaultPerPage;

    /**
     * Search term for the datatable.
     */
    #[Url(as: 'q')]
    public string $search = '';

    /**
     * Sort column for the datatable.
     */
    #[Url(as: 'sort')]
    public string $sortColumn = '';

    /**
     * Sort direction for the datatable.
     */
    #[Url(as: 'direction')]
    public string $sortDirection = 'desc';

    /**
     * Filters values for the datatable.
     */
    #[Url(as: 'filter')]
    public array $filtered = [];

    /**
     * Set the datatable maximum height.
     */
    #[Locked]
    public string $height = 'auto';

    /**
     * Determine if the datatable has a sticky header.
     */
    #[Locked]
    public bool $stickyHeader = true;

    /**
     * Determine if the datatable is bordered.
     */
    #[Locked]
    public bool $bordered = true;

    /**
     * Allowed export formats.
     */
    #[Locked]
    public array $allowedExports;

    /**
     * Determine if the datatable is exportable.
     */
    #[Locked]
    public bool $exportable = true;

    /**
     * PDF view template.
     */
    #[Locked]
    public string $pdfTemplate;

    /**
     * PDF adapter class.
     */
    #[Locked]
    public string $pdfAdapter;

    /**
     * PDF adapter options.
     */
    #[Locked]
    public array $pdfOptions = [];

    /**
     * Set the datatable empty message.
     */
    #[Locked]
    public ?string $emptyMessage = null;

    /**
     * Create a new datatable instance.
     */
    public function __construct()
    {
        $this->id ??= uniqid('datatable-');
        $this->emptyMessage ??= __('datatables::datatable.empty');
        $this->defaultPerPage = $this->perPage;

        // Set the PDF adapter and options
        $this->pdfTemplate ??= config('datatables.export.pdf.template');
        $this->pdfAdapter ??= config('datatables.export.pdf.adapter');
        $this->pdfOptions = array_merge(config('datatables.export.pdf.options') ?? [], $this->pdfOptions);

        // Set the allowed export formats
        if (! isset($this->allowedExports)) {
            $this->allowedExports = array_keys(array_filter(config('datatables.export') ?? [], fn ($export) => $export['enabled']));
        }
    }

    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        if (isset($this->model)) {
            return app($this->model)->query();
        }

        throw new Exceptions\ResourceNotFoundException('Resource not found. Please set the model property in your datatable class.');
    }

    /**
     * Get the columns for the datatable.
     */
    abstract public function columns(): array;

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return [];
    }

    /**
     * Get the default action group for the datatable.
     */
    public static function defaultActionGroup(array $actions, ?string $label = null, ?string $icon = null): array
    {
        $offset = is_mobile() ? 0 : 2;
        $count = count(array_filter($actions, fn (Action $action) => $action->visible));

        // If we have $offset + 1 actions total, just show all of them directly
        if ($count <= $offset + 1) return $actions;

        // Display the first $offset actions directly, group the rest if there are more than $offset + 1 total
        $mainActions = array_slice($actions, 0, $offset);
        $remainingActions = array_slice($actions, $offset);

        // If the remaining actions are equal to or less than 1, just show them directly
        if (count($remainingActions) <= 1) return array_merge($mainActions, $remainingActions);

        // Otherwise, show the first $offset actions and group the rest
        return array_merge(
            $mainActions,
            [ActionGroup::make($label, $icon ?? 'ti ti-dots-vertical')->actions($remainingActions)]
        );
    }

    /**
     * Get the bulk actions for the datatable.
     */
    public function bulkActions(): array
    {
        return [];
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Sort the datatable by the given column.
     */
    public function sort(?string $column = null): void
    {
        if ($column === null) {
            $this->sortColumn = '';
            $this->sortDirection = 'asc';

            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Get the pagination view.
     */
    public function paginationView(): string
    {
        return 'datatables::pagination.default';
    }

    /**
     * Export the datatable to a XLSX file.
     */
    public function toXlsx(): BinaryFileResponse
    {
        return $this->exportViaExcel('xlsx');
    }

    /**
     * Export the datatable to a CSV file.
     */
    public function toCsv(): BinaryFileResponse
    {
        return $this->exportViaExcel('csv');
    }

    /**
     * Export the datatable via the Excel package to the given format.
     */
    protected function exportViaExcel(string $format): BinaryFileResponse
    {
        $this->ensureExportIsAllowed($format);

        if (! class_exists('Maatwebsite\Excel\Excel')) {
            throw new Exceptions\MissingDependencyException(sprintf('Please install the "maatwebsite/excel" package to use the to%s method.', ucfirst($format)));
        }

        [$headings, $rows] = $this->getExportData(raw: true);

        $filename = sprintf('export-%s.%s', now()->format('Y-m-d_H-i-s'), $format);
        $rows->prepend($headings)->storeExcel($filename, null, ucfirst($format));

        $disk = config('filesystems.default');
        $root = config("filesystems.disks.$disk.root");

        return response()->download($root . '/' . $filename)->deleteFileAfterSend(true);
    }

    /**
     * Export the datatable to a JSON file.
     */
    public function toJson(): StreamedResponse
    {
        $this->ensureExportIsAllowed('json');

        [$headings, $rows] = $this->getExportData(raw: true);

        $items = $rows->map(fn ($row) => array_combine($headings, $row))->toArray();
        $filename = sprintf('export-%s.json', now()->format('Y-m-d_H-i-s'));
        $flags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(fn () => print Js::encode($items, $flags), $filename, $headers);
    }

    /**
     * Export the datatable to a PDF file.
     */
    public function toPdf(): StreamedResponse|Response
    {
        $this->ensureExportIsAllowed('pdf');

        $pdfAdapter = new $this->pdfAdapter;

        if (! $pdfAdapter instanceof Adapter || ! $pdfAdapter->supported()) {
            throw new Exceptions\MissingDependencyException(sprintf('The PDF adapter "%s" is not supported.', $this->pdfAdapter));
        }

        [$headings, $rows] = $this->getExportData(raw: false);

        return $pdfAdapter->download($this->pdfTemplate, $headings, $rows, $this->pdfOptions);
    }

    /**
     * Ensure the requested export format is available for the datatable.
     */
    protected function ensureExportIsAllowed(string $format): void
    {
        abort_unless($this->exportable && in_array($format, $this->allowedExports, true), 403);
    }

    /**
     * Get export data.
     */
    protected function getExportData(bool $raw = false): array
    {
        $columns = array_filter($this->columns(), fn (Column $column) => $column->exportable && $column->shouldRender());
        $headings = array_column($columns, 'label');

        $rows = $this->getQueryBuilder($this->filters())->get();
        $rows = $rows->map(fn ($row) => array_map(fn (Column $column) => $column->get($row, raw: $raw), $columns));

        return [$headings, $rows];
    }

    /**
     * Refresh the datatable.
     */
    public function refresh(): void
    {
        $this->resetPage();
    }

    /**
     * Run an inline action against a row.
     */
    public function runAction(string $name, mixed $key): mixed
    {
        $query = $this->query();
        $row = $query->find($key);

        if (! $row && in_array(SoftDeletes::class, class_uses_recursive($query->getModel()), true)) {
            $row = $this->query()->withTrashed()->find($key);
        }

        if (! $row) {
            throw new Exceptions\InvalidActionException("Row [$key] not found.");
        }

        $action = $this->findActionByName($name, $row);

        if (! $action || ! $action->callback) {
            throw new Exceptions\InvalidActionException("Action [$name] not found.");
        }

        if (! $action->shouldRender($row)) {
            throw new Exceptions\InvalidActionException("Action [$name] is not available for this row.");
        }

        try {
            $result = call_user_func($action->callback, $row, $this);

            if ($action->successCallback) {
                call_user_func($action->successCallback, $row, $result, $this);
            }

            return $result;
        } catch (Throwable $exception) {
            if ($action->failureCallback) {
                call_user_func($action->failureCallback, $row, $exception, $this);

                return null;
            }

            throw $exception;
        }
    }

    /**
     * Find an action by its unique name.
     */
    protected function findActionByName(string $name, Model $row): ?Action
    {
        foreach ($this->actions() as $action) {
            if ($action->isActionGroup) {
                if (! $action->shouldRender($row)) {
                    continue;
                }

                foreach ($action->actions as $groupAction) {
                    if ($groupAction->name === $name) {
                        return $groupAction;
                    }
                }

                continue;
            }

            if ($action->name === $name) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Run a bulk action against the selected rows.
     *
     * The selection lives in the browser so ticking a checkbox never hits the
     * server, which means the keys arrive here as untrusted client input.
     */
    public function runBulkAction(string $name, array $keys = []): mixed
    {
        $action = $this->findBulkActionByName($name);

        if (! $action || ! $action->callback) {
            throw new Exceptions\InvalidActionException("Bulk action [$name] not found.");
        }

        $records = $this->getSelectedRecords($keys);

        if ($records->isEmpty() || ! $action->shouldRender($records)) {
            throw new Exceptions\InvalidActionException("Bulk action [$name] is not available for the selected rows.");
        }

        try {
            $result = call_user_func($action->callback, $records, $this);

            if ($action->successCallback) {
                call_user_func($action->successCallback, $records, $result, $this);
            }

            $this->clearSelection();

            return $result;
        } catch (Throwable $exception) {
            if ($action->failureCallback) {
                call_user_func($action->failureCallback, $records, $exception, $this);

                return null;
            }

            throw $exception;
        }
    }

    /**
     * Find a bulk action by its unique name.
     */
    protected function findBulkActionByName(string $name): ?BulkAction
    {
        foreach ($this->bulkActions() as $action) {
            if ($action->name === $name) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Clear the row selection held by the browser.
     */
    protected function clearSelection(): void
    {
        $this->js('selected = []');
    }

    /**
     * Get the selected rows, scoped to the datatable query.
     */
    protected function getSelectedRecords(array $keys): Collection
    {
        $keys = array_values(array_unique(array_map('strval', array_filter($keys, 'is_scalar'))));

        if ($keys === []) {
            return new Collection;
        }

        $query = $this->query();

        if (in_array(SoftDeletes::class, class_uses_recursive($query->getModel()), true)) {
            $query->withTrashed();
        }

        return $query->whereKey($keys)->get();
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('datatables::datatable', $this->viewData());
    }

    /**
     * Get the view parameters.
     */
    public function viewData(): array
    {
        if ($this->perPage !== $this->defaultPerPage && ! in_array($this->perPage, $this->perPageOptions, true)) {
            $this->perPage = $this->defaultPerPage;
        }

        $columns = $this->getVisibleColumns();
        $actions = $this->getVisibleActions();
        $bulkActions = $this->getVisibleBulkActions();
        $filters = $this->filters();

        // Build the query and get the rows
        $query = $this->getQueryBuilder($filters);
        $rows = $query->paginate($this->perPage);

        return [
            'columns' => $columns,
            'filters' => $filters,
            'actions' => $actions,
            'bulkActions' => $bulkActions,

            'colspan' => $this->getColspanForColumns($columns, $actions, count($bulkActions) > 0),
            'filtersOpen' => count($this->filtered) > 0,

            'filterable' => count($filters) > 0,
            'selectable' => count($bulkActions) > 0,
            'searchable' => count(array_filter($columns, fn (Column $column) => $column->searchable)) > 0,
            'exportable' => $this->exportable && count($this->allowedExports) > 0 && count(array_filter($columns, fn (Column $column) => $column->exportable)) > 0,

            'rows' => $rows,
        ];
    }

    /**
     * Get the visible columns.
     */
    protected function getVisibleColumns(): array
    {
        return array_filter($this->columns(), fn (Column $column) => $column->shouldRender());
    }

    /**
     * Get the visible actions.
     */
    protected function getVisibleActions(): array
    {
        return array_filter($this->actions(), function (Action|ActionGroup $action) {
            if ($action->isActionGroup) {
                $action->actions = array_filter($action->actions, fn (Action $action) => $action->visible);

                return $action->visible && count($action->actions) > 0;
            }

            return $action->visible;
        });
    }

    /**
     * Get the visible bulk actions.
     */
    protected function getVisibleBulkActions(): array
    {
        $actions = array_filter($this->bulkActions(), fn (BulkAction $action) => $action->visible);

        // Bulk actions always live in the header dropdown, never inline.
        foreach ($actions as $action) {
            $action->grouped(true);
        }

        return $actions;
    }

    /**
     * Get the colspan for the columns.
     */
    protected function getColspanForColumns(array $columns, array $actions, bool $selectable = false): int
    {
        $colspan = count(array_filter($columns, fn (Column $column) => $column->shouldRender()));

        // Add one for the selection column
        if ($selectable) {
            $colspan++;
        }

        // Add one for the actions column
        if (count($actions) > 0) {
            $colspan++;
        }

        return $colspan;
    }

    /**
     * Get eloquent query builder.
     */
    protected function getQueryBuilder(array $filters): Builder
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);
        $this->applyGlobalSearch($query);
        $this->applySorting($query);

        return $query;
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        $globalFilters = [];
        $nestedFilters = [];

        foreach ($filters as $filter) {
            if ($filter->global) {
                $globalFilters[] = $filter;
            } else {
                $nestedFilters[] = $filter;
            }
        }

        if (count($globalFilters) > 0) {
            foreach ($globalFilters as $filter) {
                $this->applyFilter($query, $filter);
            }
        }

        $query->where(function ($query) use ($nestedFilters) {
            foreach ($nestedFilters as $filter) {
                $this->applyFilter($query, $filter);
            }
        });
    }

    protected function applyFilter(Builder $query, Filter $filter): void
    {
        $value = $this->filtered[$filter->index] ?? null;

        // Early return if the filter value is empty
        if (is_null($value) || $value === '') {
            return;
        }

        if ($filter->query) {
            call_user_func($filter->query, $query, $value);
        } else {
            $filter->apply($query, $value);
        }
    }

    /**
     * Apply global search to the query.
     */
    protected function applyGlobalSearch(Builder $query): void
    {
        if (! $this->search) {
            return;
        }

        $query->where(function ($query) {
            foreach ($this->columns() as $column) {
                if (! $column->searchable) {
                    continue;
                }

                if (is_callable($column->searcher)) {
                    call_user_func($column->searcher, $query, $this->search);

                    continue;
                }

                if ($column->relationship) {
                    $this->searchWithinRelation($query, $column->name);
                } else {
                    $query->orWhere($column->name, 'like', '%' . $this->search . '%');
                }
            }
        });
    }

    /**
     * Search within relation.
     */
    protected function searchWithinRelation(Builder $query, string $column): void
    {
        $this->orWithRelation($column, $query, function (Builder $query, string $field) {
            $query->where($field, 'like', '%' . $this->search . '%');
        });
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting(Builder $query): void
    {
        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'desc';
        }

        if (! $this->sortColumn) {
            $primaryKey = $this->query()->getModel()->getKeyName();
            $query->orderBy($primaryKey, $this->sortDirection);

            return;
        }

        // Find the column to sort by
        $column = Arr::first($this->columns(), function ($column) {
            return $column->sortable && $column->name === $this->sortColumn;
        });

        if (! $column) {
            $this->sortColumn = '';

            $primaryKey = $this->query()->getModel()->getKeyName();
            $query->orderBy($primaryKey, $this->sortDirection);

            return;
        }

        if ($column->sorter) {
            call_user_func($column->sorter, $query, $this->sortDirection);

            return;
        }

        if ($column->relationship) {
            $this->sortWithinRelation($query, $column->name);
        } else {
            $query->orderBy($column->name, $this->sortDirection);
        }
    }

    /**
     * Sort within relation.
     */
    protected function sortWithinRelation(Builder $query, string $column): void
    {
        $relations = explode('.', $column);
        $field = array_pop($relations);

        // The name of the aggregate column
        $name = Str::snake(implode(' ', [...$relations, $field]));

        if (count($relations) === 1) {
            $query->withAggregate($relations[0], $field);
        } else {
            if (is_null($query->getQuery()->columns)) {
                $query->select($query->getModel()->qualifyColumn('*'));
            }

            $query->selectSub($this->nestedRelationSubquery($query, $relations, $field)->limit(1), $name);
        }

        $query->orderBy($name, $this->sortDirection);
    }

    /**
     * Build a correlated subquery for a column reached through nested relations.
     */
    protected function nestedRelationSubquery(Builder $parentQuery, array $relations, string $field): Builder
    {
        $relationName = array_shift($relations);
        $relation = Relation::noConstraints(fn () => $parentQuery->getModel()->{$relationName}());
        $relatedQuery = $relation->getRelated()->newQuery();

        if (count($relations) === 0) {
            $query = $relation->getRelationExistenceQuery(
                $relatedQuery,
                $parentQuery,
                $relation->getRelated()->qualifyColumn($field),
            );
        } else {
            $query = $relation->getRelationExistenceQuery($relatedQuery, $parentQuery);
            $query->select([])->selectSub(
                $this->nestedRelationSubquery($relatedQuery, $relations, $field)->limit(1),
                'nested_relation_value',
            );
        }

        return $query->mergeConstraintsFrom($relation->getQuery());
    }
}
