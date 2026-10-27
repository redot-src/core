@props([
    'icon' => 'ti ti-circle-x',
    'title' => __('Nothing to show here'),
    'subtitle' => __('Trust me, I\'ve looked everywhere, there\'s nothing here :('),
])

<div {{ $attributes->class(['card']) }}>
    <div class="empty">
        @if ($icon)
            <div class="empty-icon">
                <x-icon :icon="$icon" style="font-size: 3rem" />
            </div>
        @endif

        <p class="empty-title">
            {{ $title }}
        </p>

        @if ($subtitle)
            <p class="empty-subtitle text-secondary">
                {{ $subtitle }}
            </p>
        @endif

        @if (isset($slot) && $slot->isNotEmpty())
            <div class="empty-action">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
