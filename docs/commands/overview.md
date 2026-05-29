# Artisan Commands

Redot Core registers a set of Artisan commands that handle build steps, maintenance chores, permission discovery, and the database-driven translation workflow. They are all wired up in `Redot\RedotServiceProvider::register()`, so they are available in any consuming app the moment the package is installed — no extra registration is required.

This page covers the operational commands. The scaffolding generators (`entity:make`, `view:make`, `model:populate`) are documented separately on the [Scaffolding & Stubs](/commands/scaffolding-and-stubs) page.

## Registered commands

The service provider registers these classes via `$this->commands([...])`:

| Command | Class | Purpose |
| --- | --- | --- |
| `dependencies:build` | `BuildDependenciesCommand` | Compile translations and JS init files into the dist directory |
| `uploads:clear` | `ClearUploadsCommand` | Delete everything under `public/uploads` |
| `lint` | `LintCommand` | Run Pint (and optionally Prettier) |
| `public:link` | `PublicLinkCommand` | Symlink `public/` to a public web root |
| `permissions:sync` | `SyncPermissionsCommand` | Auto-discover route permissions into Spatie |
| `lang:extract` | `ExtractLanguageTokensCommand` | Pull tokens out of source into the DB |
| `lang:publish` | `PublishLanguageTokensCommand` | Write DB tokens to lang files |
| `lang:revert` | `RevertLanguageTokensCommand` | Reset tokens to their original values |
| `lang:sync` | `SyncLanguageTokensCommand` | Sync DB tokens with the lang files |

The scaffolding commands (`EntityMakeCommand`, `ViewMakeCommand`, `ModelPopulateCommand`) are registered alongside these but documented on the [Scaffolding & Stubs](/commands/scaffolding-and-stubs) page.

## `dependencies:build`

```
php artisan dependencies:build
```

Compiles two kinds of front-end dependencies into `public/assets/dist` (the path returned by the `dist_path()` helper) and records a lock file describing what was built.

What it produces:

- **Per-locale translation bundles** — for every locale in `config('app.locales')`, it reads `lang/{locale}.json` plus every `lang/{locale}/*.php` file, flattens the PHP files with `Arr::dot()`, merges them, and writes a JS file to `dist_path('translations')/{locale}.js`. Each file sets `window.__locale` and `window.__translations` so the browser can read translations directly.
- **Init bundle** — every file under `public/assets/inits/*.js` is wrapped in an IIFE and assigned to `window.__inits["{name}"]`, then concatenated into `dist_path()/init.js`. In the dashboard these are integration shims like `coloris.js`, `tinymce.js`, `tomselect.js`, `query-builder.js`, and `uploader.js`.
- **Lock file** — `dist_path('lock.json')` stores the `filemtime()` of every source file and directory the build depended on (keyed by path relative to `base_path()`), so a downstream tooling step can detect when a rebuild is needed.

Run it after changing translation files or anything under `public/assets/inits`. Note that locales come from `config('app.locales')`, which `RedotServiceProvider` populates at boot from the `Language` table (falling back to `config('redot.locales')`). See [Localization](/foundation/localization).

## `uploads:clear`

```
php artisan uploads:clear
```

Deletes every file under `public/uploads`. It collects the files first; if the directory is empty it prints an error and exits with code `1`. Otherwise it asks for confirmation (`Are you sure you want to delete all files in the uploads directory?`) and, if confirmed, deletes them one by one with a progress bar. This command is interactive — there is no `--force` flag.

## `lint`

```
php artisan lint
php artisan lint --with-js
```

Runs Laravel Pint via `vendor/bin/pint` against the whole project. With the `--with-js` flag it additionally runs `npx prettier --write` over `base_path()`, but only if `npm` is detected (`npm -v` returns success); otherwise it skips the JS step with a notice.

In the dashboard this is the canonical formatting entry point. It is exposed as a Composer script and used in CI:

```json
// dashboard composer.json
"scripts": {
    "lint": [
        "@php artisan lint --with-js"
    ]
}
```

```yaml
# .github/workflows/lint.yml
- run: php artisan lint --with-js
```

So locally you typically just run `composer lint`.

## `public:link`

```
php artisan public:link
php artisan public:link --name=public_html
```

Creates a symbolic link from the framework's `public/` directory to a sibling directory in the project root, named by the `--name` option (default `public_html`). This is for shared-hosting layouts where the web root must be called `public_html`. If a directory or link with that name already exists it errors out with exit code `1` rather than overwriting it.

## `permissions:sync`

```
php artisan permissions:sync
```

Auto-discovers permissions from your routes and persists them as Spatie permission records. It scans every registered route and keeps only those that:

1. have a route name,
2. respond to `GET` or `DELETE`, and
3. include the `Redot\Http\Middleware\RoutePermission` middleware.

For each matching route it calls `Permission::firstOrCreate(['name' => $route->getName()])`, so the permission name **is the route name**. Existing permissions are left untouched.

In the dashboard this runs from the role seeder so a fresh install always has its permissions in place:

```php
// database/seeders/RoleSeeder.php
Artisan::call('permissions:sync');
```

Run it (or re-seed) whenever you add or rename permission-guarded routes. See [Middleware](/foundation/middleware) for `RoutePermission`.

## Language token commands

These four commands drive the database-backed translation workflow. Each one takes an optional `language` argument (a language `code`). When supplied, the command looks up `Redot\Models\Language::where('code', $code)->firstOrFail()` and processes just that language; when omitted, it iterates over `Language::all()`. In every case the work is delegated to a queued job that is run synchronously via `dispatchSync()`, so the command blocks until the job completes.

### `lang:extract`

```
php artisan lang:extract
php artisan lang:extract ar
```

Extracts translation tokens from the source code into the database by dispatching `Redot\Jobs\ExtractLanguageTokens` for each language.

### `lang:sync`

```
php artisan lang:sync
php artisan lang:sync en
```

Syncs the database tokens with the on-disk language files via `Redot\Jobs\SyncLanguageTokens`.

### `lang:publish`

```
php artisan lang:publish
php artisan lang:publish ar
```

Writes the database tokens back out to the language files via `Redot\Jobs\PublishLanguageTokens`.

### `lang:revert`

```
php artisan lang:revert
php artisan lang:revert ar
```

Reverts tokens to their original values via `Redot\Jobs\RevertLanguageTokens`.

A typical lifecycle is: `lang:extract` to gather strings, edit translations (via the DB / UI), then `lang:publish` to write them to files. See [Localization](/foundation/localization) for how languages and tokens fit together. Because the jobs are dispatched synchronously, no queue worker is required to run these commands, but the `language` codes must already exist in the `languages` table — an unknown code throws `ModelNotFoundException` from `firstOrFail()`.

## Notes

- All operational commands are registered unconditionally in `register()`, so they are usable in console contexts without any publishing step.
- The interactive prompts (`info`, `error`, `confirm`, `progress`) use Laravel Prompts. `uploads:clear` in particular will pause for confirmation — keep that in mind in non-interactive environments.
- `dependencies:build` writes only to `public/assets/dist`; it never modifies your source `lang/` or `public/assets/inits` files.
