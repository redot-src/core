# Scaffolding & Stubs

Redot Core ships a set of Artisan generator commands that scaffold full CRUD entities for the dashboard with a single command, plus a stub-publishing system so you can customize the generated Blade views. The headline command is `make:entity`, which orchestrates Laravel's own generators (`make:model`, `make:controller`, etc.) together with a customized `make:view` and the package's `make:datatable`.

All three commands documented here are registered in `Redot\RedotServiceProvider` and are available out of the box once the package is installed.

## Commands at a glance

| Command | Class | Purpose |
| --- | --- | --- |
| `make:entity` | `Redot\Commands\EntityMakeCommand` | Scaffold a complete dashboard resource (model, migration, controller, views, datatable, test) |
| `make:view` | `Redot\Commands\ViewMakeCommand` | Generate a Blade view from a stub template with parameter substitution |
| `model:populate` | `Redot\Commands\ModelPopulateCommand` | Run a model factory to seed the database with fake data |

## `make:entity`

```bash
make:entity {name?} {--features=}
```

`make:entity` is an interactive, batteries-included scaffolder. Given a single entity name it generates everything needed for a CRUD section of the dashboard.

- `name` (optional): the singular, StudlyCase resource name (e.g. `Post`). If omitted you are prompted for it. The name **must contain only alphabetic characters** (`/^[a-zA-Z]+$/`) — anything else aborts with an error.
- `--features=`: a comma-separated list of features to scaffold (parsed via `parse_csv`). If omitted, an interactive multiselect prompt is shown.

### Available features

The feature keys are: `factory`, `migration`, `seeder`, `controller`, `request`, `views`, `datatable`, `test`.

The default selection (when using the interactive prompt) is: `factory`, `migration`, `controller`, `views`, `datatable`, `test`. Note that `seeder` and `request` are **not** selected by default.

### What gets generated

The command normalizes the name and derives several string variants, then delegates to other generators:

- `$name = Str::studly($name)` — e.g. `Post`
- `$plural = Str::plural($name)` — e.g. `Posts`
- `$entity = Str::camel($name)` — e.g. `post`
- `$resource = strtolower(Str::kebab($plural))` — e.g. `posts`
- `$datatable = Str::snake($plural, '-')` — e.g. `posts`

Before doing anything it guards against collisions: if `app/Models/{Name}.php` already exists, the command errors out.

| Feature | Generator call | Result |
| --- | --- | --- |
| (always) model | `make:model` with `-f`/`-m`/`-s` flags driven by selected features | `app/Models/{Name}.php` (+ factory/migration/seeder) |
| `controller` | `make:controller Dashboard/{Name}Controller --model={Name} --requests=<request?>` | `app/Http/Controllers/Dashboard/{Name}Controller.php` |
| `views` | `make:view` x4 (see below) | `resources/views/dashboard/{resource}/{index,create,edit,show}.blade.php` |
| `datatable` | `make:datatable {plural} --model={Name}` | Livewire datatable component |
| `test` | `make:test Http/Controllers/Dashboard/{Name}ControllerTest` | Feature/unit test |

The `request` feature is not a standalone generator — it only toggles `--requests` on the controller generation (which produces form request classes). It is therefore only meaningful when `controller` is also selected.

### How views are scaffolded

For each of `index`, `create`, `edit`, `show`, the command calls `make:view` with a stub template and params:

```php
$this->call('make:view', [
    'name' => "views/dashboard/$resource/$view",
    '--template' => "dashboard.$template",
    '--params' => "resource=$resource&entity=$entity&datatable=$datatable",
]);
```

The `index` view uses the `dashboard.index-datatable` template when the `datatable` feature is selected, otherwise it falls back to `dashboard.index`. The `create`, `edit`, and `show` views always use the matching `dashboard.create` / `dashboard.edit` / `dashboard.show` template.

### Example

```bash
# Fully interactive — prompts for name and features
php artisan make:entity

# Non-interactive, all the defaults
php artisan make:entity Post --features=factory,migration,controller,views,datatable,test

# A read-mostly resource without a datatable or test
php artisan make:entity Category --features=migration,controller,views
```

Running `php artisan make:entity Post` with the default features produces, end to end:

```
app/Models/Post.php
database/factories/PostFactory.php
database/migrations/xxxx_xx_xx_create_posts_table.php
app/Http/Controllers/Dashboard/PostController.php
resources/views/dashboard/posts/index.blade.php
resources/views/dashboard/posts/create.blade.php
resources/views/dashboard/posts/edit.blade.php
resources/views/dashboard/posts/show.blade.php
app/Livewire/Datatables/Posts.php          # via make:datatable
tests/Feature/Http/Controllers/Dashboard/PostControllerTest.php
```

See [Datatables](/packages/datatables/overview) for what `make:datatable` produces and how the generated `<livewire:datatables.posts />` component works.

## `make:view`

```bash
make:view {name} {--template=} {--params=}
```

`Redot\Commands\ViewMakeCommand` extends Laravel's built-in `view:make`/`make:view` command and adds two options:

- `--template` / `-t`: the stub template name (without the `.stub` extension). When omitted it falls back to the framework's default `stubs/view.stub`.
- `--params` / `-p`: a URL-encoded query string of replacement values (e.g. `resource=posts&entity=post`).

### Stub resolution

Templates are resolved by `resolveStubPath()`, which **prefers a project-local stub over the packaged one**:

1. `base_path('stubs/{template}.stub')` — your published/customized copy
2. `<package>/stubs/{template}.stub` — the default shipped with redot/core

If neither exists, a `FileNotFoundException` is thrown.

### Parameter substitution

Params are parsed with `parse_str`, flattened with `Arr::dot`, and then every <span v-pre>`{{ key }}`</span> placeholder in the rendered stub is replaced with its value. So `--params="resource=posts&entity=post"` replaces <span v-pre>`{{ resource }}`</span> with `posts` and <span v-pre>`{{ entity }}`</span> with `post`.

> Note: substitution is a literal string replace of <span v-pre>`{{ key }}`</span> tokens that came from the stub. The stubs intentionally use <span v-pre>`{{ resource }}`</span>, <span v-pre>`{{ entity }}`</span>, and <span v-pre>`{{ datatable }}`</span> as substitution markers, while leaving Blade expressions like <span v-pre>`{{ __('Create') }}`</span> and <span v-pre>`{{ back_or_route(...) }}`</span> (which are not param keys) intact in the output.

### Example

```bash
php artisan make:view views/dashboard/posts/index \
    --template=dashboard.index-datatable \
    --params="resource=posts&entity=post&datatable=posts"
```

## Stub files

The package ships these stubs under `stubs/`. They are the templates `make:entity` feeds to `make:view`.

| Stub | Used for | Placeholders |
| --- | --- | --- |
| `dashboard.index.stub` | index view without datatable | `resource` |
| `dashboard.index-datatable.stub` | index view with datatable | `resource`, `datatable` |
| `dashboard.create.stub` | create form | `resource` |
| `dashboard.edit.stub` | edit form | `resource`, `entity` |
| `dashboard.show.stub` | detail view | `resource` |
| `website.page.stub` | a public website page | — |

For example, `dashboard.index-datatable.stub` is:

```blade
<x-layouts::dashboard>
    <x-page-header :create="route('dashboard.{{ resource }}.create')" class="mb-3" />
    <livewire:datatables.{{ datatable }} />
</x-layouts::dashboard>
```

and `dashboard.edit.stub` references the routed model instance via the `entity` placeholder:

```blade
<x-layouts::dashboard>
    <x-form class="card" :action="route('dashboard.{{ resource }}.update', ${{ entity }})" method="PUT">
        ...
    </x-form>
</x-layouts::dashboard>
```

After running `make:entity Admin`, the generated `resources/views/dashboard/admins/index.blade.php` in the dashboard app looks exactly like the resolved stub:

```blade
<x-layouts::dashboard>
    <x-page-header :create="route('dashboard.admins.create')" class="mb-3" />
    <livewire:datatables.admins />
</x-layouts::dashboard>
```

The `create`, `edit`, and `show` stubs leave a <span v-pre>`<!-- {{ quote }} -->`</span> marker in the card body for you to fill in form fields / detail rows — <span v-pre>`{{ quote }}`</span> is not one of the params `make:entity` passes, so it remains in the output as a reminder of where to add content.

### Publishing & customizing stubs

The stubs are published under the `redot::stubs` tag (mapped to your app's `stubs/` directory):

```bash
php artisan vendor:publish --tag=redot::stubs
```

Once published, edits to `base_path('stubs/dashboard.*.stub')` take precedence over the packaged versions thanks to the resolution order in `make:view`. This lets you tailor the scaffolded markup project-wide without touching the package.

## `model:populate`

```bash
model:populate {--model=} {--count=}
```

`Redot\Commands\ModelPopulateCommand` runs a model's factory to seed the database with fake records — handy for filling out a new datatable during development.

- `--model=`: the fully-qualified model class to populate. If omitted you choose interactively.
- `--count=`: how many records to create. If omitted you are prompted (default `10`, must be greater than `0`).

### Model discovery

The command scans `app/Models`, maps each file to `{AppNamespace}Models\{Name}`, merges in any classes from the static `ModelPopulateCommand::$include` array, and keeps only classes that exist, expose a `factory()` method, and are not listed in the static `ModelPopulateCommand::$execlude` array. Both `$include` and `$execlude` are public static arrays you can populate to extend or restrict the candidate list.

Interactive selection adapts to the OS: a `search()` prompt is used everywhere except Windows, which uses a plain `choice()` prompt (to work around a known Prompts search issue on Windows).

### Performance note

During population the command sets `Hash::setRounds(4)` so factories that hash passwords run fast, then restores the rounds to `env('BCRYPT_ROUNDS', 12)` afterwards. Progress is shown with a `progress()` bar over `$count` steps.

### Example

```bash
# Interactive: pick a model, then a count
php artisan model:populate

# Non-interactive
php artisan model:populate --model="App\\Models\\User" --count=50
```

## Gotchas & defaults

- `make:entity` name validation is strict — alphabetic characters only; numbers, spaces, or symbols abort the command.
- It refuses to run if a model of the same name already exists in `app/Models`.
- `request` and `datatable` are toggles for other generators: `request` only adds `--requests` to the controller, and `datatable` switches the index view stub and triggers `make:datatable`.
- Default features omit `seeder` and `request`.
- Project-local stubs in `base_path('stubs/')` always win over packaged stubs; publish with the `redot::stubs` tag to customize.
- `model:populate` temporarily lowers bcrypt rounds to 4 and restores them on completion.
