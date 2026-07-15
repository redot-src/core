# Artisan Commands

Redot Core adds a set of Artisan commands for build steps, maintenance
chores, permission discovery, and the database-driven translation workflow.
They are available the moment the package is installed — no registration needed.

This page covers the operational commands. The scaffolding generators
(`make:entity`, `make:view`, `model:populate`) are documented on the
[Scaffolding & Stubs](/commands/scaffolding-and-stubs) page.

## `dependencies:build`

```bash
php artisan dependencies:build
```

Compiles the front-end runtime bundles — per-locale translation files and the
component initializer bundle — into the dist directory, and records a lock file
so a rebuild can be detected later. Run it after changing translation files or
anything under `public/assets/inits`, and during deploy so the first web request
doesn't pay for the build. See the [Asset & Init System](/frontend/asset-system)
for how these bundles are loaded.

## `uploads:clear`

```bash
php artisan uploads:clear
```

Deletes every file under `public/uploads`. It is interactive — it lists what it
found and asks for confirmation before deleting, with a progress bar. There is
no force flag, so keep that in mind in non-interactive environments.

## `lint`

```bash
php artisan lint
php artisan lint --with-js
```

Runs Laravel Pint over the whole project. This is the canonical formatting entry
point for the project (also exposed as `composer lint`).

- **`--with-js`** — additionally runs Prettier over the project, but only when
  `npm` is available; otherwise the JS step is skipped with a notice.

## `public:link`

```bash
php artisan public:link
php artisan public:link --name=public_html
```

Creates a symbolic link from the framework's `public/` directory to a sibling
directory in the project root. Use it on shared-hosting layouts where the web
root must have a specific name. It will not overwrite an existing directory or
link.

- **`--name`** — the name of the link to create (defaults to `public_html`).

## `datatables:link`

```bash
php artisan datatables:link
php artisan datatables:link --force
php artisan datatables:link --relative
```

Creates a symbolic link from `public/vendor/datatables` to the Datatables
package assets. Required once per install (and again after upgrades) so the
table's CSS/JS are served statically. Prefer
`vendor:publish --tag=datatables::assets` if symlinks are unavailable.

- **`--force`** — recreate the symlink if it already exists.
- **`--relative`** — create a relative symlink.

## `permissions:sync`

```bash
php artisan permissions:sync
```

Auto-discovers permission-guarded routes and persists them as Spatie permission
records. Discovery includes every HTTP verb. Conventional `store` routes share
their `create` permission, conventional `update` routes share their `edit`
permission, and overrides configured with `usePermission()` or resource-level
`usePermissions()` use their explicit permissions. Existing permissions are
left untouched. Run it (or re-seed)
whenever you add or rename permission-guarded routes. See
[Middleware](/foundation/middleware) for permission resolution and opt-out
details.

## Language token commands

These four commands drive the database-backed translation workflow. Each takes
an optional language `code` argument; when supplied, only that language is
processed, otherwise every language is. They run synchronously, so no queue
worker is required — but the language code must already exist.

A typical lifecycle is `lang:extract` to gather strings, edit translations in
the dashboard, then `lang:publish` to write them to files. See
[Localization](/foundation/localization) for how languages and tokens fit
together.

### `lang:extract`

```bash
php artisan lang:extract
php artisan lang:extract ar
```

Extracts translation tokens from the source code into the database.

- **`language`** — process only the language with this code (optional).

### `lang:sync`

```bash
php artisan lang:sync
php artisan lang:sync en
```

Syncs the database tokens with the on-disk language files.

- **`language`** — process only the language with this code (optional).

### `lang:publish`

```bash
php artisan lang:publish
php artisan lang:publish ar
```

Writes the database tokens back out to the language files.

- **`language`** — process only the language with this code (optional).

### `lang:revert`

```bash
php artisan lang:revert
php artisan lang:revert ar
```

Reverts tokens to their original values.

- **`language`** — process only the language with this code (optional).

## Related

- [Scaffolding & Stubs](/commands/scaffolding-and-stubs) — the `make` generators.
- [Asset & Init System](/frontend/asset-system) — what `dependencies:build` produces.
- [Localization](/foundation/localization) — the language token workflow.
