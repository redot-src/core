<div class="card mb-2">
    <div class="card-header">
        <span class="text-muted cursor-grab" sortable-handle>
            <i class="ti ti-grip-vertical"></i>
        </span>

        <div class="card-actions">
            <button type="button" class="btn btn-icon" action="insert" title="{{ __('Insert') }}">
                <x-icon icon="ti ti-plus" />
            </button>

            <button type="button" class="btn btn-icon" action="remove" title="{{ __('Remove') }}">
                <x-icon icon="ti ti-x" />
            </button>
        </div>
    </div>

    <div {{ $attributes->class(['card-body']) }}>
        {{ $slot }}
    </div>
</div>
