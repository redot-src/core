// Deattach dropdown menu from datatable-actions dropdown
$(document).on('show.bs.dropdown', '.datatable-actions .dropdown', (event) => {
    const $dropdown = $(event.target).closest('.dropdown');
    const $menu = $dropdown.find('.dropdown-menu');

    // Append the wire:id to the dropdown menu to keep the context as wire-context
    const $root = $dropdown.closest('[wire\\:id]');
    $menu.attr('parent-wire-id', $root.attr('wire:id'));

    // Append the dropdown menu to the body
    $menu.appendTo('body');
});

// Handle datatable action click
$(document).on('click', '.datatable-action[method]:not([method="get"])', (event) => {
    event.preventDefault();

    // Get the action element
    const $action = $(event.target).closest('.datatable-action');

    // Define the callback function
    const callback = function () {
        const $form = $(`<form action="${$action.attr('href')}" method="POST" disable-validation></form>`);

        // Spoof the form method
        $form.append(`<input type="hidden" name="_method" value="${$action.attr('method')}">`);
        $form.append(`<input type="hidden" name="_token" value="${$action.attr('token')}">`);

        // Get request body
        let body = JSON.parse(atob($action.attr('request-body')));

        // Append request body to the form
        if (body && typeof body === 'object') {
            Object.entries(body).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((item) => {
                        $form.append(`<input type="hidden" name="${key}[]" value="${item}">`);
                    });
                } else {
                    $form.append(`<input type="hidden" name="${key}" value="${value}">`);
                }
            });
        }

        $form.appendTo('body').submit();
    };

    // Early return if no confirmation is required
    if ($action.hasAttr('confirm') === false) {
        return callback();
    }

    // Use warnBeforeAction if available
    if (typeof warnBeforeAction !== 'undefined') {
        return warnBeforeAction(callback, { content: $action.attr('confirm') });
    }

    // Fallback to native confirm
    if (confirm($action.attr('confirm'))) {
        callback();
    }
});

// Handle inline datatable action click
$(document).on('click', '.datatable-action[action-name]', (event) => {
    event.preventDefault();

    const $action = $(event.target).closest('.datatable-action');
    const name = $action.attr('action-name');
    const key = $action.attr('action-key');

    let wireId = $action.closest('[parent-wire-id]').attr('parent-wire-id');
    if (!wireId) wireId = $action.closest('[wire\\:id]').attr('wire:id');

    const wire = window.Livewire.find(wireId);

    const run = () => wire.call('runAction', name, key);

    if ($action.hasAttr('confirm') === false) {
        return run();
    }

    if (typeof warnBeforeAction !== 'undefined') {
        return warnBeforeAction(run, { content: $action.attr('confirm') });
    }

    if (confirm($action.attr('confirm'))) {
        run();
    }
});
