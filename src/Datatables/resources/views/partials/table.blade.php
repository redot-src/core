<div class="table-responsive border-top">
    <table @class([
        'table card-table table-vcenter datatable',
        'bordered' => $bordered,
    ])>
        @if ($rows->isEmpty() === false)
            <thead @class(['sticky-top' => $stickyHeader])>
                <tr>
                    @if ($selectable)
                        <th class="w-1 datatable-select"
                            x-data="{ page: @js($rows->map(fn($row) => (string) $row->getKey())->values()) }">
                            <input type="checkbox" class="form-check-input m-0 align-middle"
                                aria-label="@lang('datatables::datatable.bulk.select_page')" wire:click="toggleSelection"
                                x-bind:checked="page.every((key) => $wire.selected.includes(key))"
                                x-bind:indeterminate="page.some((key) => $wire.selected.includes(key)) &&
                                    !page.every((key) => $wire.selected.includes(key))" />
                        </th>
                    @endif

                    @foreach ($columns as $column)
                        <th @class(['fixed-' . $column->fixedDirection => $column->fixed])>
                            @if ($column->sortable && $column->name)
                                <span class="text-decoration-none cursor-pointer" wire:click="sort('{{ $column->name }}')"
                                    wire:click.shift="sort(null)">
                                    <span class="me-1">
                                        {{ $column->label }}
                                    </span>

                                    @if ($sortColumn === $column->name)
                                        @if ($sortDirection === 'asc')
                                            <i class="fas fa-sort-up"></i>
                                        @else
                                            <i class="fas fa-sort-down"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort"></i>
                                    @endif
                                </span>
                            @else
                                {{ $column->label }}
                            @endif
                        </th>
                    @endforeach

                    @if ($actions)
                        <th class="w-1 fixed-end datatable-actions"></th>
                    @endif
                </tr>
            </thead>
        @endif

        <tbody wire:loading.class="opacity-50">
            @forelse($rows as $row)
                <tr wire:key="{{ $id }}-row-{{ $row->getKey() }}">
                    @if ($selectable)
                        <td class="datatable-select">
                            <input type="checkbox" class="form-check-input m-0 align-middle"
                                aria-label="@lang('datatables::datatable.bulk.select_row')" value="{{ $row->getKey() }}"
                                wire:model.live="selected" />
                        </td>
                    @endif

                    @foreach ($columns as $column)
                        <td {{ $column->buildAttributes($row) }}>
                            {!! $column->get($row) !!}
                        </td>
                    @endforeach

                    @if ($actions)
                        <td class="fixed-end datatable-cell datatable-actions">
                            <div class="d-flex gap-1">
                                @foreach ($actions as $action)
                                    @if ($action->shouldRender($row))
                                        @if ($action->isActionGroup)
                                            @include('datatables::partials.action-group')
                                        @else
                                            @include('datatables::partials.action')
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colspan }}" class="text-center text-muted">
                        @include('datatables::partials.empty')
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
