<x-assets.redot-icon-picker />

<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <div class="row g-2 w-100" iconpicker-wrapper>
        <div class="col input-icon">
            <span class="input-icon-addon">
                <i class="icon icon-sm {{ $attributes->get('value') }}" iconpicker-preview></i>
            </span>

            <input type="text" id="{{ $id }}" autocomplete="off"
                {{ $attributes->class(['form-control'])->merge(['init' => 'icon-picker', 'iconpicker-source' => hashed_asset('vendor/redot/libraries/tabler-icons/tabler-icons.json')]) }} />
        </div>

        <div class="col-auto">
            <button type="button" class="btn btn-icon" tabindex="-1" iconpicker-picker>
                <i class="ti ti-search"></i>
            </button>
        </div>
    </div>
</x-field>
