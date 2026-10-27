<div role="alert" {{ $attributes }}>
    @if ($icon)
        <x-icon :icon="$icon" class="alert-icon icon" />
    @endif

    <div>
        @php($body = $description ?: (isset($slot) && $slot->isNotEmpty() ? $slot : null))

        @if ($title && $body)
            <h4 class="alert-title">{{ $title }}</h4>
            <div class="text-secondary">{{ $body }}</div>
        @else
            {{ $title ?: $body }}
        @endif
    </div>

    @if ($dismissible)
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    @endif
</div>
