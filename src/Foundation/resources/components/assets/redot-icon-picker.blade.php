@pushOnce('plugins-scripts', 'icon-picker-scripts')
    <script src="{{ hashed_asset('/vendor/redot/plugins/redot-icon-picker.js') }}"></script>
@endPushOnce

@pushOnce('templates', 'icon-picker-template')
    <template iconpicker-template>
        <nav class="nav nav-segmented w-100">
            <button type="button" class="nav-link active" iconpicker-style="all">
                <i class="ti ti-grid-dots"></i> {{ __('All') }}
            </button>
            <button type="button" class="nav-link" iconpicker-style="outline">
                <i class="ti ti-circle"></i> {{ __('Outline') }}
            </button>
            <button type="button" class="nav-link" iconpicker-style="filled">
                <i class="ti ti-circle-filled"></i> {{ __('Filled') }}
            </button>
        </nav>

        <div class="input-icon">
            <span class="input-icon-addon">
                <i class="ti ti-search"></i>
            </span>
            <input type="text" class="form-control" placeholder="{{ __('Search for an icon') }}..." iconpicker-search />
        </div>

        <div iconpicker-list>
            <p iconpicker-empty>{{ __('No icons found.') }}</p>
        </div>

        <div class="row g-3">
            <div class="col-6">
                <label class="form-label mb-1">{{ __('Animation') }}</label>
                <x-select :tom="false" iconpicker-animation>
                    <option value="">{{ __('None') }}</option>
                    <option value="icon-pulse">{{ __('Pulse') }}</option>
                    <option value="icon-tada">{{ __('Tada') }}</option>
                    <option value="icon-rotate">{{ __('Rotate') }}</option>
                </x-select>
            </div>
            <div class="col-6">
                <label class="form-label mb-1">{{ __('Rotation') }}</label>
                <x-select :tom="false" iconpicker-rotation>
                    <option value="">{{ __('None') }}</option>
                    <option value="icon-rotate-90">90°</option>
                    <option value="icon-rotate-180">180°</option>
                    <option value="icon-rotate-270">270°</option>
                    <option value="icon-flip-horizontal">{{ __('Flip Horizontal') }}</option>
                    <option value="icon-flip-vertical">{{ __('Flip Vertical') }}</option>
                </x-select>
            </div>
        </div>
    </template>
@endPushOnce
