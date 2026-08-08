<span class="text-secondary me-auto">
    {{ trans_choice('datatables::datatable.bulk.selected', count($selected), ['count' => count($selected)]) }}
</span>

@foreach ($bulkActions as $bulkAction)
    @include('datatables::partials.action', [
        'action' => $bulkAction,
        'row' => null,
    ])
@endforeach

<button type="button" class="btn btn-icon" title="@lang('datatables::datatable.bulk.clear')" wire:click="clearSelection">
    <i class="fas fa-close"></i>
</button>
