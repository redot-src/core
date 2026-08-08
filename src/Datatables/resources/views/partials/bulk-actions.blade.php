<div class="d-flex align-items-center gap-2 datatable-bulk-actions">
    <span class="text-secondary" x-text="selectedLabel(@js(__('datatables::datatable.bulk.selected')))"></span>

    <div class="dropdown ms-auto">
        <a href="#" class="btn dropdown-toggle" data-bs-toggle="dropdown" title="@lang('datatables::datatable.bulk.actions')"
            aria-label="@lang('datatables::datatable.bulk.actions')">
            @lang('datatables::datatable.bulk.actions')
        </a>

        <div class="dropdown-menu dropdown-menu-end">
            <span class="dropdown-header" x-text="selectedLabel(@js(__('datatables::datatable.bulk.selected')))"></span>

            @foreach ($bulkActions as $bulkAction)
                @include('datatables::partials.action', [
                    'action' => $bulkAction,
                    'row' => null,
                ])
            @endforeach

            <div class="dropdown-divider"></div>

            <a href="#" class="dropdown-item" x-on:click.prevent="clearSelection()">
                <span class="dropdown-item-icon">
                    <i class="fas fa-close"></i>
                </span>

                <span class="dropdown-item-title">
                    @lang('datatables::datatable.bulk.cancel')
                </span>
            </a>
        </div>
    </div>
</div>
