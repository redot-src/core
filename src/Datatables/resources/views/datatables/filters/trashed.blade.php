<select class="form-select" id="filter-{{ $filter->index }}" wire:model.live="filtered.{{ $filter->index}}">
    <option value="without">@lang('redot::datatables.filters.trashed.without')</option>
    <option value="with">@lang('redot::datatables.filters.trashed.with')</option>
    <option value="only">@lang('redot::datatables.filters.trashed.only')</option>
</select>
