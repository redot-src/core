<x-assets.redot-uploader />

<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <div uploader-container class="position-relative">
        <input type="hidden" id="{{ $id }}" value="{{ $value }}" {{ $attributes }} />

        <div uploader-wrapper class="form-control overflow-hidden rounded-3 p-0">
            <div uploader-scroll class="position-relative" data-overlayscrollbars-initialize init="overlay-scrollbars"
                os-viewport=":scope > [uploader-list]" os-slot="self" os-overflow.x="hidden">
                <div uploader-list></div>
            </div>

            <button type="button" uploader-trigger
                class="d-flex align-items-center gap-2 w-100 overflow-hidden border-0 text-start user-select-none">
                <i class="ti ti-plus flex-shrink-0" aria-hidden="true"></i>

                <span uploader-labels class="text-truncate">
                    <span uploader-idle>
                        <span uploader-action class="fw-medium">{{ __('Add files') }}</span>
                        <span uploader-empty>{{ __('or drop them here') }}</span>
                    </span>

                    <span uploader-dropping class="fw-medium">{{ __('Drop to upload') }}</span>
                </span>
            </button>
        </div>
    </div>
</x-field>
