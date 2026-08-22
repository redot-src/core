<?php

namespace Redot\Datatables;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Traits\Macroable;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Actions\BulkAction;
use Redot\Datatables\Columns\Column;
use Redot\Datatables\Exporters\ExportManager;
use Redot\Datatables\Filters\Filter;
use Redot\Toastify\Concerns\InteractsWithToastify;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

abstract class Datatable extends Component
{
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
     * Sort state for the datatable (`title,-age`; a leading `-` is descending).
     */
    #[Url(as: 'sort')]
    public string $sortColumn = '';

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
     *
     * Clicking a column cycles unsorted → asc → desc → unsorted and
     * replaces any existing sort. Shift-click appends a column and
     * cycles that key in place.
     */
    public function sort(?string $column = null, bool $append = false): void
    {
        $state = SortState::fromString($this->sortColumn);

        $state = match (true) {
            $column === null => SortState::empty(),
            $append => $state->cycleAppend($column),
            default => $state->cycle($column),
        };

        $this->sortColumn = $state->toString();
    }

    /**
     * Get the active sorts keyed by column name.
     */
    protected function sorts(): \Illuminate\Support\Collection
    {
        return SortState::fromString($this->sortColumn)->all();
    }

    /**
     * Get the pagination view.
     */
    public function paginationView(): string
    {
        return 'datatables::pagination.default';
    }

    /**
     * Export the datatable to a file in the given format.
     */
    public function export(string $format): BinaryFileResponse|StreamedResponse|Response
    {
        $manager = new ExportManager($this->exportable, $this->allowedExports);
        $manager->ensureAllowed($format);

        $exporter = $manager->exporter($format, $format === 'pdf' ? [
            'template' => $this->pdfTemplate,
            'adapter' => $this->pdfAdapter,
            'options' => $this->pdfOptions,
        ] : []);

        // Fail on missing dependencies before running the export query.
        $exporter->ensureSupported();

        [$headings, $rows] = $this->getExportData(raw: $exporter->raw());

        return $exporter->download($headings, $rows);
    }

    /**
     * Export the datatable to a XLSX file.
     */
    public function toXlsx(): BinaryFileResponse|StreamedResponse|Response
    {
        return $this->export('xlsx');
    }

    /**
     * Export the datatable to a CSV file.
     */
    public function toCsv(): BinaryFileResponse|StreamedResponse|Response
    {
        return $this->export('csv');
    }

    /**
     * Export the datatable to a JSON file.
     */
    public function toJson(): BinaryFileResponse|StreamedResponse|Response
    {
        return $this->export('json');
    }

    /**
     * Export the datatable to a PDF file.
     */
    public function toPdf(): BinaryFileResponse|StreamedResponse|Response
    {
        return $this->export('pdf');
    }

    /**
     * Get export data.
     */
    protected function getExportData(bool $raw = false): array
    {
        $columns = array_filter($this->columns(), fn (Column $column) => $column->exportable && $column->shouldRender());
        $headings = array_column($columns, 'label');

        $rows = $this->buildQuery()->get();
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
        $row = $this->queryIncludingTrashed()->find($key);

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

        return $this->invoke($action, $row);
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

        return $this->invoke($action, $records, onSuccess: fn () => $this->clearSelection());
    }

    /**
     * Invoke an action's callback, routing the outcome through its
     * success and failure callbacks.
     */
    protected function invoke(Action $action, mixed $subject, ?Closure $onSuccess = null): mixed
    {
        try {
            $result = call_user_func($action->callback, $subject, $this);

            if ($action->successCallback) {
                call_user_func($action->successCallback, $subject, $result, $this);
            }

            if ($onSuccess) {
                $onSuccess($result);
            }

            return $result;
        } catch (Throwable $exception) {
            if (! $action->failureCallback) {
                throw $exception;
            }

            call_user_func($action->failureCallback, $subject, $exception, $this);

            return null;
        }
    }

    /**
     * Find an action by its unique name.
     */
    protected function findActionByName(string $name, Model $row): ?Action
    {
        foreach ($this->actions() as $action) {
            if ($action->isActionGroup) {
                if ($action->shouldRender($row) && ($found = $action->find($name))) {
                    return $found;
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

        return $this->queryIncludingTrashed()->whereKey($keys)->get();
    }

    /**
     * Get the base query including soft-deleted rows when the model supports them.
     */
    protected function queryIncludingTrashed(): Builder
    {
        $query = $this->query();

        if (in_array(SoftDeletes::class, class_uses_recursive($query->getModel()), true)) {
            $query->withTrashed();
        }

        return $query;
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
        $rows = $this->buildQuery($filters)->paginate($this->perPage);

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

            'sorts' => $this->sorts(),
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
     * Get the visible actions, groups are copied so the originals stay untouched.
     */
    protected function getVisibleActions(): array
    {
        $actions = array_map(function (Action|ActionGroup $action) {
            if ($action->isActionGroup) {
                $visible = array_filter($action->actions, fn (Action $action) => $action->visible);

                return $action->visible && count($visible) > 0 ? $action->withActions($visible) : null;
            }

            return $action->visible ? $action : null;
        }, $this->actions());

        return array_filter($actions);
    }

    /**
     * Get the visible bulk actions.
     */
    protected function getVisibleBulkActions(): array
    {
        return array_filter($this->bulkActions(), fn (BulkAction $action) => $action->visible);
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
     * Build the datatable query with filters, search, and sorting applied.
     */
    protected function buildQuery(?array $filters = null): Builder
    {
        $query = $this->query();

        $this->applyFilters($query, $filters ?? $this->filters());
        $this->applySearch($query);
        $this->applySorting($query);

        return $query;
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        [$global, $nested] = collect($filters)->partition(fn (Filter $filter) => $filter->global);

        foreach ($global as $filter) {
            $filter->applyTo($query, $this->filtered[$filter->index] ?? null);
        }

        if ($nested->isNotEmpty()) {
            $query->where(function (Builder $query) use ($nested) {
                foreach ($nested as $filter) {
                    $filter->applyTo($query, $this->filtered[$filter->index] ?? null);
                }
            });
        }
    }

    /**
     * Apply global search to the query.
     */
    protected function applySearch(Builder $query): void
    {
        if (! $this->search) {
            return;
        }

        $columns = array_filter($this->columns(), fn (Column $column) => $column->searchable);

        if ($columns === []) {
            return;
        }

        $query->where(function (Builder $query) use ($columns) {
            foreach ($columns as $column) {
                $column->applySearch($query, $this->search);
            }
        });
    }

    /**
     * Apply sorting to the query, falling back to the primary key descending.
     */
    protected function applySorting(Builder $query): void
    {
        $columns = collect($this->columns());
        $applied = false;

        foreach ($this->sorts() as $name => $direction) {
            $column = $columns->first(fn (Column $column) => $column->sortable && $column->name === $name);

            if ($column) {
                $column->applySort($query, $direction);
                $applied = true;
            }
        }

        if (! $applied) {
            $query->orderBy($query->getModel()->getKeyName(), 'desc');
        }
    }
}
