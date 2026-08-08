<div class="dropdown datatable-bulk-actions" x-show="selected.length > 0" x-cloak>
    <a href="#" class="btn dropdown-toggle" data-bs-toggle="dropdown" title="@lang('datatables::datatable.bulk.actions')"
        aria-label="@lang('datatables::datatable.bulk.actions')">
        <span class="d-none d-sm-inline" x-text="selectedLabel(@js(__('datatables::datatable.bulk.selected')))"></span>
        <span class="d-sm-none" x-text="selected.length"></span>
    </a>

    <div class="dropdown-menu dropdown-menu-end">
        <span class="dropdown-header" x-text="selectedLabel(@js(__('datatables::datatable.bulk.selected')))"></span>

        @foreach ($bulkActions as $bulkAction)
            @include('datatables::partials.action', [
                'action' => $bulkAction,
                'row' => null,
            ])
        @endforeach
    </div>
</div>
