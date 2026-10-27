@if ($title)
    <x-label :title="$title" :for="$id" />
@endif

<div attachments-container id="{{ $id }}"
    {{ $attributes->class(['form-control', 'overflow-hidden', 'rounded-3', 'p-0']) }}>
    <div attachments-scroll class="position-relative" data-overlayscrollbars-initialize init="overlay-scrollbars"
        os-viewport=":scope > [attachments-list]" os-slot="self" os-overflow.x="hidden">
        <div attachments-list>
            @forelse ($attachments as $attachment)
                <a href="{{ $attachment['url'] }}"
                    class="uploader-item position-relative d-flex align-items-center gap-2 overflow-hidden text-decoration-none"
                    download>
                    <div
                        class="uploader-item-preview position-relative d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden rounded-2">
                        @if (str_starts_with($attachment['type'], 'image/'))
                            <img class="uploader-item-image w-100 h-100"
                                src="{{ $attachment['thumbnail'] ?? $attachment['url'] }}"
                                alt="{{ $attachment['name'] }}">
                        @else
                            <i class="{{ $getFileIcon($attachment['type']) }} text-secondary"></i>
                        @endif
                    </div>

                    <div class="uploader-item-info d-flex align-items-baseline gap-2 flex-fill">
                        <div class="uploader-item-name flex-fill text-truncate fw-medium">{{ $attachment['name'] }}</div>
                        <div class="uploader-item-size flex-shrink-0 text-secondary">
                            {{ $formatFileSize($attachment['size']) }}
                        </div>
                    </div>

                    <div class="uploader-item-actions d-flex align-items-center flex-shrink-0">
                        <span class="btn btn-icon" aria-hidden="true">
                            <i class="ti ti-download"></i>
                        </span>
                    </div>
                </a>
            @empty
                <div attachments-none class="d-flex align-items-center text-secondary">{{ __('No attachments') }}</div>
            @endforelse
        </div>
    </div>
</div>
