<div class="d-flex align-items-center gap-2 datatable-bulk-actions">
    <span class="text-secondary" x-text="selectedLabel(@js(__('redot::datatables.bulk.selected')))"></span>

    <div class="dropdown ms-auto">
        <a href="#" class="btn dropdown-toggle" data-bs-toggle="dropdown" title="@lang('redot::datatables.bulk.actions')"
            aria-label="@lang('redot::datatables.bulk.actions')">
            @lang('redot::datatables.bulk.actions')
        </a>

        <div class="dropdown-menu dropdown-menu-end">
            <span class="dropdown-header" x-text="selectedLabel(@js(__('redot::datatables.bulk.selected')))"></span>

            @foreach ($bulkActions as $bulkAction)
                @include('redot::datatables.partials.action', [
                    'action' => $bulkAction,
                    'row' => null,
                ])
            @endforeach

            <div class="dropdown-divider"></div>

            <a href="#" class="dropdown-item" x-on:click.prevent="clearSelection()">
                <span class="dropdown-item-icon">
                    <i class="ti ti-x"></i>
                </span>

                <span class="dropdown-item-title">
                    @lang('redot::datatables.bulk.cancel')
                </span>
            </a>
        </div>
    </div>
</div>
