<select class="form-select" id="filter-{{ $filter->index }}" wire:model.live="filtered.{{ $filter->index}}">
    <option value="">{{ $filter->placeholder }}</option>

    @foreach ($filter->options as $key => $value)
        <option value="{{ $key }}">{{ $value }}</option>
    @endforeach
</select>
