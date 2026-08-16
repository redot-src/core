<button type="button" :class="{ 'active': filtersOpen }" class="btn btn-icon" x-on:click="toggleFilters()">
    <i class="ti ti-filter"></i>
</button>

@if ($filtered)
    <button type="button" class="btn btn-icon" wire:click="$set('filtered', [])">
        <i class="ti ti-x"></i>
    </button>
@endif
