<select class="form-select" id="filter-{{ $filter->index }}" wire:model.live="filtered.{{ $filter->index}}">
    <option value="without">@lang('datatables::datatable.filters.trashed.without')</option>
    <option value="with">@lang('datatables::datatable.filters.trashed.with')</option>
    <option value="only">@lang('datatables::datatable.filters.trashed.only')</option>
</select>
