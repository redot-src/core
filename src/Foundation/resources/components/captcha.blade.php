<x-assets.cloudflare-turnstile />

<x-field :id="$id" :title="$title" :hint="$hint" :required="true">
    <div id="{{ $id }}" {{ $attributes }}>
        <input type="hidden" name="{{ $name }}" validation="required">
    </div>
</x-field>
