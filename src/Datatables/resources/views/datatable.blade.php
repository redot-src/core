@assets
    <link rel="stylesheet" href="{{ hashed_asset(config('datatables.assets.css')) }}" />
    <script src="{{ hashed_asset(config('datatables.assets.js')) }}" defer></script>
@endassets

<div class="card datatable" @style(['max-height: ' . $height]) id="{{ $id }}" wire:ignore.self x-data="Datatables.table({ filtersOpen: @js((bool) $filtersOpen) })">
    <div class="card-body d-flex align-items-center gap-1">
        <div class="w-auto me-auto" wire:ignore>
            @include('datatables::partials.per-page')
        </div>

        @if ($searchable)
            <div wire:ignore>
                @include('datatables::partials.search')
            </div>
        @endif

        @if ($exportable)
            <div wire:ignore>
                @include('datatables::partials.export')
            </div>
        @endif

        @if ($filterable)
            <div class="d-flex align-items-center gap-1">
                @include('datatables::partials.filters-toggle')
            </div>
        @endif

        <div wire:ignore>
            @include('datatables::partials.refresh')
        </div>
    </div>

    @if ($filterable)
        <div class="card-body" wire:ignore x-show="filtersOpen" x-cloak>
            @include('datatables::partials.filters')
        </div>
    @endif

    @if ($selectable)
        <div class="card-body" wire:ignore x-show="selected.length > 0" x-cloak>
            @include('datatables::partials.bulk-actions')
        </div>
    @endif

    @include('datatables::partials.table')

    <div class="card-footer">
        @include('datatables::partials.pagination')
    </div>
</div>
