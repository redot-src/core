# Redot Core - Agent Rules

- Laravel package for Redot Dashboard, published as `redot/core`.
- PHP 8.3+, Laravel 13+, Livewire 4.2+.
- Package type: Composer library, not a standalone dashboard app.
- In-repo packages:
    - `Redot\Auth`
    - `Redot\Datatables`
    - `Redot\LangExtractor`
    - `Redot\Sidebar`
    - `Redot\Toastify`
- Tests: Pest 4 with Orchestra Testbench.
- Formatting: `.editorconfig`, `pint.json`, Laravel Pint.

## The Three Rules

1. **Don't change the package contract.**
    - No app-level dashboard rewrites, SPA assumptions, Inertia, Vue, React, or frontend build changes.
    - No new runtime packages, service providers, global helpers, publish tags, config keys, routes, middleware behavior, or public APIs unless the task requires it.
    - Preserve the public surface consumed by Redot Dashboard: namespaces, helper names, config shape, stubs, migrations, Blade directives, validation rules, commands, and package services.
2. **Reuse before you add.**
    - If a helper, trait, cast, rule, model, command pattern, service provider pattern, datatable class, or test fixture already exists, use it.
    - Don't create a parallel implementation under a new name.
3. **Make the smallest safe change.**
    - Don't refactor, rename, move, or restructure anything the task didn't ask for.
    - Match nearby naming, validation, exceptions, return types, config style, and formatting.

## Where Things Live

| Layer                       | Path                                                                                 |
| --------------------------- | ------------------------------------------------------------------------------------ |
| Root package code           | `src`                                                                                |
| Service provider            | `src/RedotServiceProvider.php`                                                       |
| Package config              | `config/redot.php`                                                                   |
| Console commands            | `src/Commands`                                                                       |
| HTTP controllers/middleware | `src/Http/Controllers`, `src/Http/Middleware`                                        |
| Models                      | `src/Models`                                                                         |
| Casts, rules, traits        | `src/Casts`, `src/Rules`, `src/Traits`                                               |
| Jobs and notifications      | `src/Jobs`, `src/Notifications`                                                      |
| Shared support/helpers      | `src/Support`, `src/helpers.php`                                                     |
| Package migrations          | `database/migrations`                                                                |
| Generator/view stubs        | `stubs`                                                                              |
| Auth package                | `src/packages/auth/src`                                                              |
| Datatables package          | `src/packages/datatables/src`, plus `config`, `routes`, `resources`, `lang`          |
| Lang extractor package      | `src/packages/lang-extractor/src`                                                    |
| Sidebar package             | `src/packages/sidebar/src`                                                           |
| Toastify package            | `src/packages/toastify/src`, plus `config` and `resources`                           |
| Core tests                  | `tests/Feature/Core`, `tests/Unit/Core`                                              |
| Package tests               | `tests/Feature/Packages`, `tests/Unit/Packages`                                      |
| Test fixtures               | `tests/Fixtures`                                                                     |

- Don't touch the following unless explicitly asked:
    - `vendor`
    - `.phpunit.cache`
    - generated files under `storage` if present
    - consuming-app files outside this repository

## Patterns To Follow

- **Composer and package registration**
    - Keep PSR-4 namespaces in `composer.json` aligned with the directory layout.
    - Preserve `Redot\RedotServiceProvider` auto-discovery unless explicitly asked.
    - Add dependencies only with approval and only when the package cannot reasonably use Laravel or existing dependencies.
- **Service providers**
    - Follow existing provider methods: `mergeConfigFrom`, `publishes`, `publishesMigrations`, `loadViewsFrom`, `loadTranslationsFrom`, and `loadRoutesFrom`.
    - Keep publish tags stable (`redot::config`, `redot::stubs`, `redot::migrations`, package-specific tags).
    - Be careful with boot-time behavior: Blade directives, paginator views, rate limiters, URL defaults, validation rules, JSON casts, and destructive command protection affect every consuming app.
- **Config**
    - Put Redot config surface in `config/redot.php`.
    - Settings entries may include `default` and `rules`; keep the existing array shape.
    - Document meaningful new config behavior in README or related docs when adding it.
- **Controllers and responses**
    - Use `Redot\Http\Controllers\Controller` and its response helpers (`created()`, `updated()`, `deleted()`, `restored()`, `success()`, `error()`, `warning()`, `info()`).
    - Preserve API response behavior from `Redot\Traits\RespondAsApi`.
- **Models, casts, rules, traits**
    - Follow standard Laravel conventions: casts, fillable properties, relationships, factories only when needed by tests.
    - Keep validation rules compatible with Laravel validator extension usage in `RedotServiceProvider`.
    - Prefer existing traits (`CanUploadFile`, `Taggable`, `UserAuditable`) before adding model-level helpers.
- **Migrations**
    - Package-owned schema lives in `database/migrations` and is published into consuming apps.
    - Keep migration names/timestamps consistent with existing package migrations.
    - Schema changes must include focused tests and should preserve upgrade compatibility for consuming dashboards.
- **Commands**
    - Add commands under `src/Commands` and register them in `RedotServiceProvider` only when they are part of the package surface.
    - Keep generated output compatible with existing stubs and dashboard conventions.
- **Auth package**
    - Follow the action/contract/route registrar structure in `src/packages/auth/src`.
    - Preserve `RedotAuth` facade behavior and guard/provider assumptions unless explicitly asked.
- **Datatables package**
    - Use existing action, column, filter, exception, and trait classes before adding new primitives.
    - Keep datatable routes, views, CSS, JS, and translations namespaced under the package.
    - Query-related behavior should remain compatible with Eloquent builders and Livewire.
- **Blade, assets, and translations**
    - Package views live under namespaced resources such as `datatables::...` or Toastify resources.
    - UI strings in package views should be translatable.
    - Only edit package CSS/JS resources when the behavior belongs in the package, not in the consuming app.
- **Stubs**
    - Stubs are generated into consuming apps; preserve dashboard route/view/component conventions.
    - Treat stub changes as user-facing and verify the generated shape where possible.

## Database

- This repo's automated tests use Orchestra Testbench with an in-memory SQLite connection.
- Do not connect to or mutate a consuming dashboard database unless explicitly asked.
- Read-only inspection is allowed when needed:
    - `SHOW TABLES`
    - `DESCRIBE`
    - `EXPLAIN`
    - `SELECT`
- **Never** run any of the following against a real database without explicit approval:
    - `INSERT`, `UPDATE`, `DELETE`
    - `DROP`, `ALTER`, `TRUNCATE`, `CREATE`
    - migrations, seeders, or destructive Artisan commands
- Schema changes go through package migrations in `database/migrations`.
- If a DB change is needed, show the migration/SQL and wait when the change affects a real consuming app.
- Don't expose sensitive user data unless debugging requires it.

## Workflow

- **Before editing**
    - Read the relevant source, package provider, config, migration, stub, tests, and consuming surface.
    - Read referenced files in full.
    - Trace bugs end-to-end before deciding the cause: config/provider registration -> route/middleware/command -> model/helper/rule/trait -> view/asset/stub -> tests.
- **While editing**
    - Keep changes localized.
    - Follow nearby patterns.
    - Comment only non-obvious reasoning.
    - Preserve validation, authorization, translation, escaping, publish tags, and backward compatibility.
    - Add focused Pest tests when behavior changes.
- **After editing**
    - Run the narrowest useful check first.
    - Full suite: `composer test`.
    - Lint: `composer lint`.
    - If you can't run either, say so and flag the remaining risk.

## Build, Test, and Development Commands

- Install dependencies: `composer install`.
- Run tests: `composer test` or `vendor/bin/pest`.
- Run a focused test: `vendor/bin/pest tests/Unit/Core/HelpersTest.php`.
- Run lint/formatting: `composer lint` or `vendor/bin/pint`.
- Run coverage when needed: `composer coverage`.
- There is no local web server command here; validate package behavior through tests and, when needed, through the consuming Laravel dashboard.

## Ask First If The Change Touches

- Package architecture
- Public APIs, namespaces, helper names, or facade behavior
- Composer dependencies or autoloading
- Service provider registration or boot-time global behavior
- Config keys, defaults, setting rules, or publish tags
- Database structure or real database mutation
- Auth, permissions, guards, tokens, or security-sensitive behavior
- Routes, middleware, rate limiting, localization, or URL defaults
- Generated stubs or consuming-app file organization
- Datatable action/filter/column contracts
- Translation strategy or published resources
- Backward compatibility for existing Redot Dashboard installs

When asking:

- State what's unclear.
- List options.
- Recommend the safest one.
- Note side effects.

**Don't ask** when the change is:

- Small
- Isolated
- Low-risk
- Covered by nearby tests or patterns
- Touches none of the above

State any meaningful assumption.

## Response Style

- Brief explanation of the issue, then the fix.
- Name the files/functions changed.
- Flag side effects and compatibility impact.
- If multiple solutions exist, recommend the safest one for this codebase.

---

This is production package code. Stability, backward compatibility, permission safety, and consistency with existing Redot/Laravel patterns beat modernization or unrelated refactors.
