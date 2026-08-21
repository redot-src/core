<div class="table-responsive border-top">
    <table @class([
        'table card-table table-vcenter datatable',
        'bordered' => $bordered,
    ])>
        @if ($rows->isEmpty() === false)
            <thead @class(['sticky-top' => $stickyHeader])>
                <tr>
                    @if ($selectable)
                        @php($pageKeys = $rows->map(fn ($row) => (string) $row->getKey())->values())

                        {{-- Keyed on the page contents so Alpine re-reads $pageKeys whenever the rows change. --}}
                        <th class="w-1 datatable-select" wire:key="{{ $id }}-select-page-{{ md5($pageKeys->implode(',')) }}"
                            x-data="Datatables.pageSelection(@js($pageKeys))">
                            <input type="checkbox" class="form-check-input m-0 align-middle"
                                aria-label="@lang('datatables::datatable.bulk.select_page')"
                                x-bind:checked="allSelected" x-bind:indeterminate="indeterminate"
                                x-on:click="toggle()" />
                        </th>
                    @endif

                    @foreach ($columns as $column)
                        <th @class(['fixed-' . $column->fixedDirection => $column->fixed])>
                            @if ($column->sortable && $column->name)
                                @php($direction = $sorts->get($column->name))

                                <span class="text-decoration-none cursor-pointer"
                                    wire:click="sort('{{ $column->name }}', $event.shiftKey)">
                                    <span class="me-1">
                                        {{ $column->label }}
                                    </span>

                                    @if ($direction)
                                        <i class="ti {{ $direction === 'asc' ? 'ti-sort-ascending' : 'ti-sort-descending' }}"></i>

                                        @if ($sorts->count() > 1)
                                            <small class="text-muted">{{ $sorts->keys()->search($column->name) + 1 }}</small>
                                        @endif
                                    @else
                                        <i class="ti ti-arrows-sort"></i>
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
                                x-model="selected" />
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
