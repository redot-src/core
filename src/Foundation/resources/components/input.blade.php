<x-field :id="$id" :title="$title" :hint="$hint" :required="$required">
    <div @class([
        'col',
        'w-100',
        'input-group' => $isInputGroup,
        'input-group-flat' => $isInputGroup && $flat,
    ])>
        @if ($prepend instanceof \Illuminate\View\ComponentSlot)
            {{ $prepend }}
        @elseif ($prepend)
            <span class="input-group-text">{{ $prepend }}</span>
        @endif

        <input type="{{ $type }}" id="{{ $id }}" {{ $attributes->class(['form-control']) }} />

        @if ($isPassword)
            <button type="button" class="input-group-text" tabindex="-1" password-toggle>
                <i class="ti ti-eye"></i>
            </button>
        @endif

        @if ($append instanceof \Illuminate\View\ComponentSlot)
            {{ $append }}
        @elseif ($append)
            <span class="input-group-text">{{ $append }}</span>
        @endif
    </div>
</x-field>

@if ($isPassword)
    @pushOnce('scripts', 'password-toggle-scripts')
        <script>
            $(document).on('click', '[password-toggle]', function() {
                const $input = $(this).closest('.input-group').find('input');
                const isPassword = $input.attr('type') === 'password';

                $input.attr('type', isPassword ? 'text' : 'password');
                $(this).find('i').toggleClass('ti-eye ti-eye-off');
            });
        </script>
    @endPushOnce
@endif
