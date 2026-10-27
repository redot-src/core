{{-- Core --}}
<script src="{{ hashed_asset('/vendor/redot/libraries/jquery/jquery.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/redot/libraries/lodash/lodash.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/redot/libraries/tabler/js/tabler.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/redot/js/functions.js') }}"></script>

{{-- Plugins --}}
<script src="{{ hashed_asset('/vendor/redot/libraries/bootbox/bootbox.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/redot/libraries/glare/glare.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/redot/libraries/sortable/sortable.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/redot/libraries/overlayscrollbars/overlayscrollbars.min.js') }}"></script>
<script src="{{ hashed_asset('/vendor/redot/plugins/redot-visibility.js') }}"></script>
<script src="{{ hashed_asset('/vendor/redot/plugins/redot-validator.js') }}"></script>
<script src="{{ hashed_asset('/vendor/redot/plugins/redot-validator-rules.js') }}"></script>

{{-- Language Script --}}
<script src="{{ hashed_asset('/assets/dist/translations/' . app()->getLocale() . '.js') }}"></script>

{{-- Initializers --}}
<script src="{{ hashed_asset('/assets/dist/init.js') }}"></script>

{{-- Runtime --}}
<script src="{{ hashed_asset('/vendor/redot/js/runtime.js') }}"></script>

<x-toasts />

<script>
    window.OldBag = _.toPlainObject(@json(old()));
    window.ErrorsBag = _.toPlainObject(@json(isset($errors) ? $errors->getMessages() : []));
</script>

@if (isset($errors) && $errors->any())
    <script>
        $(document).ready(() => {
            if (window.OldBag.hasOwnProperty('_form')) {
                $form = $(`[name="_form"][value="${window.OldBag._form}"`).closest('form');
            } else {
                $form = $('body');
            }

            appendErrorsToForm($form, window.ErrorsBag);
            scrollToFirstError($form);
        });
    </script>
@endif
