<div class="dropdown datatable-bulk-actions" x-show="selected.length > 0" x-cloak>
    <a href="#" class="btn btn-icon" data-bs-toggle="dropdown" title="@lang('datatables::datatable.bulk.actions')"
        aria-label="@lang('datatables::datatable.bulk.actions')">
        <i class="fas fa-tasks"></i>
    </a>

    <div class="dropdown-menu dropdown-menu-end">
        <span class="dropdown-header"
            x-text="@js(__('datatables::datatable.bulk.selected')).replace(':count', selected.length)"></span>

        @foreach ($bulkActions as $bulkAction)
            @include('datatables::partials.action', [
                'action' => $bulkAction,
                'row' => null,
            ])
        @endforeach
    </div>
</div>
