<select class="form-select" id="filter-{{ $filter->index }}" wire:model.live="filtered.{{ $filter->index}}">
    <option value="">{{ $filter->placeholder }}</option>
    <option value="yes">{{ $filter->labels['yes'] }}</option>
    <option value="no">{{ $filter->labels['no'] }}</option>

    @if ($filter->empty)
        <option value="empty">{{ $filter->labels['empty'] }}</option>
    @endif
</select>
