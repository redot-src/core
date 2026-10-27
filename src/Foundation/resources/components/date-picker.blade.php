<x-assets.tempus-dominus />

<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <div class="col input-group input-group-flat">
        <input type="text" id="{{ $id }}" autocomplete="off" readonly
            {{ $attributes->class(['form-control'])->merge(['init' => 'tempus-dominus']) }} />

        <span class="input-group-text">
            <x-icon icon="ti ti-calendar" />
        </span>
    </div>
</x-field>
