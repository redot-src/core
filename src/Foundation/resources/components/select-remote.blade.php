<x-select :id="$id" :value="$value" {{ $attributes }}>
    {{ $slot }}

    @if ($create)
        <x-slot:append>
            <a href="{{ route($create, $createParams) }}" class="btn btn-icon" data-lightbox data-type="iframe">
                <i class="ti ti-plus"></i>
            </a>
        </x-slot:append>
    @endif
</x-select>
