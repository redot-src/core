<button type="button" :class="{ 'active': filtersOpen }" class="btn btn-icon" x-on:click="toggleFilters()">
    <i class="fas fa-filter"></i>
</button>

@if ($filtered)
    <button type="button" class="btn btn-icon" wire:click="$set('filtered', [])">
        <i class="fas fa-close"></i>
    </button>
@endif
