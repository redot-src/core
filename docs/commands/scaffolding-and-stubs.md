# Scaffolding & Stubs

Redot Dashboard ships generator commands that scaffold a full CRUD section with
one command, plus a stub system so you can customize the markup they emit. The
headline command is `make:entity`; it drives Laravel's own generators together
with `make:view` and the datatable generator.

## `make:entity`

```bash
php artisan make:entity Post
php artisan make:entity Post --features=factory,migration,controller,views,datatable,test
php artisan make:entity
```

Given a single entity name, `make:entity` generates everything needed for a CRUD
section: model, migration, controller, Blade views, a datatable, and a test. Run
it with no arguments to be prompted for the name and features interactively.

The name must be a singular, StudlyCase, alphabetic-only word (e.g. `Post`).
The command refuses to run if a model of the same name already exists.

- **`name`** — the resource name (optional; prompted if omitted).
- **`--features`** — comma-separated list of pieces to scaffold (a multiselect
  prompt is shown if omitted).

### Available features

Pick any of: `factory`, `migration`, `seeder`, `controller`, `request`, `views`,
`datatable`, `test`. The interactive default selects `factory`, `migration`,
`controller`, `views`, `datatable`, and `test` — `seeder` and `request` are off
by default.

- **`request`** — toggles form-request generation on the controller, so it is
  only meaningful alongside `controller`.
- **`datatable`** — generates a Livewire datatable and switches the index view
  to the datatable-backed template.

Running `php artisan make:entity Post` with the defaults produces a model and
migration, a `Dashboard/PostController`, the four CRUD views under
`resources/views/dashboard/posts/`, a `posts` datatable, and a controller test.
See [Datatables](/packages/datatables/overview) for what the generated datatable
gives you.

## `make:view`

```bash
php artisan make:view views/dashboard/posts/index \
    --template=dashboard.index-datatable \
    --params="resource=posts&entity=post&datatable=posts"
```

Generates a Blade view from a stub template, substituting parameters into it.
`make:entity` calls this for each CRUD view, but you can use it directly too.

- **`--template`** (`-t`) — the stub to render (without the `.stub` extension);
  falls back to the framework's default view stub when omitted.
- **`--params`** (`-p`) — a URL-encoded query string of replacement values.

### How parameters substitute

Each <span v-pre>`{{ key }}`</span> placeholder in the stub is replaced with the
matching param value, so `--params="resource=posts&entity=post"` turns
<span v-pre>`{{ resource }}`</span> into `posts` and
<span v-pre>`{{ entity }}`</span> into `post`. The stubs use
<span v-pre>`{{ resource }}`</span>, <span v-pre>`{{ entity }}`</span>, and
<span v-pre>`{{ datatable }}`</span> as substitution markers while leaving real
Blade expressions like <span v-pre>`{{ __('Create') }}`</span> untouched.

A project-local stub in your app's `stubs/` directory always wins over the
packaged version, so publishing and editing stubs is how you tailor the
generated markup project-wide.

## Stub files

The package ships these stubs, the templates `make:entity` feeds to `make:view`:

- **`dashboard.index`** — index view without a datatable.
- **`dashboard.index-datatable`** — index view with a datatable.
- **`dashboard.create`** — create form.
- **`dashboard.edit`** — edit form.
- **`dashboard.show`** — detail view.
- **`website.page`** — a public website page.

The `create`, `edit`, and `show` stubs leave a marker in the card body for you
to fill in form fields or detail rows.

### Publishing & customizing stubs

```bash
php artisan vendor:publish --tag=redot::stubs
```

This copies the stubs into your app's `stubs/` directory. Once published, your
edited copies take precedence over the packaged ones, so the next scaffold uses
your markup — no need to touch the package.

## `model:populate`

```bash
php artisan model:populate
php artisan model:populate --model="App\\Models\\User" --count=50
```

Runs a model's factory to seed the database with fake records — handy for
filling out a new datatable during development. Run it with no options to pick a
model and count interactively.

- **`--model`** — the model class to populate (prompted if omitted).
- **`--count`** — how many records to create (prompted if omitted, default 10).

Password hashing rounds are temporarily lowered during population so factories
that hash passwords run fast, then restored afterwards.

## Related

- [Datatables](/packages/datatables/overview) — the generated datatable component.
- [Artisan Commands](/commands/overview) — the operational commands.
- [Layouts](/layouts/overview) — the layouts the scaffolded views wrap in.
