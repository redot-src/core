<div class="input-group mb-3" id="filter-{{ $filter->index }}">
    <input type="date" class="form-control" wire:model.live="filtered.{{ $filter->index}}.from">
    <input type="date" class="form-control" wire:model.live="filtered.{{ $filter->index}}.to">
</div>
