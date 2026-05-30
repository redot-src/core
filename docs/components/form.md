# Form

`<x-form>` is the standard form wrapper. It renders the `<form>`
tags, injects the CSRF token, spoofs the HTTP method when needed, and stamps a
hidden form identifier — so every page-level form is
built on top of it.

## Usage

```blade
<x-form :action="route('posts.store')" method="POST">
    <!-- fields and buttons -->
</x-form>
```

Put your fields and buttons inside the default slot. Any extra attribute
(`class`, `id`, …) is forwarded to the underlying `<form>` element.

## Options

- **`action`** — the form action URL.
- **`method`** — the HTTP verb (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`).
  Real verbs other than `GET`/`POST` are sent as `POST` with a spoofed method
  field automatically.
- **`route`** — a named route used to build the action instead of `action`.
- **`routeParams`** — parameters passed to the named route.
- **`enctype`** — the form encoding type. Defaults to `multipart/form-data` so
  file uploads work without extra config.
- **`id`** — the form id; auto-generated when omitted.
- **`disable-validation`** — opt the form out of client-side validation (see
  [RedotValidator](/frontend/plugins/redot-validator)).

## Examples

### Create form

```blade
<x-form class="card card-md" :action="route('posts.store')" method="POST">
    <div class="card-body">
        <div class="mb-3">
            <x-input name="title" :title="__('Title')" validation="required" />
        </div>
        <div class="mb-3">
            <x-textarea name="body" :title="__('Body')" validation="required" />
        </div>
        <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">{{ __('Save') }}</button>
        </div>
    </div>
</x-form>
```

### Update form with method spoofing

```blade
<x-form class="card" :action="route('posts.update', $post)" method="PUT">
    <div class="card-body">
        <x-input type="file" name="cover" :title="__('Cover')" />
    </div>
</x-form>
```

### Utility form opted out of validation

```blade
<x-form id="delete-post" :action="route('posts.destroy', $post)" method="DELETE" class="d-none" disable-validation />
```

## Related

- [Form Card](/components/form-card) — a card scaffold built on `<x-form>` for resource create/edit pages.
- [RedotValidator](/frontend/plugins/redot-validator) — the submit-time validation engine.
- [Components overview](/components/overview) — using components and shared conventions.
