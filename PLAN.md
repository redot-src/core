# Test Coverage Improvement Plan

This plan covers the `redot/core` Laravel package, including the root package code, bundled subpackages, stubs, configuration, migrations, views, and package bootstrapping behavior. The repository already contains Pest, Orchestra Testbench, `phpunit.xml`, and an empty `tests/Feature` / `tests/Unit` structure, so the first goal is to turn the existing harness into a reliable package test suite.

## Goals

- Prove the package boots cleanly inside Orchestra Testbench with Laravel 13, PHP 8.3, SQLite in memory, and all bundled service providers.
- Cover public behavior rather than implementation details, especially service providers, commands, middleware, routes, validation rules, model behavior, jobs, helpers, and package APIs.
- Add regression tests for all code paths that touch files, configuration, routes, permissions, localization, authentication flows, datatables, language extraction, sidebar composition, toast rendering, and generated stubs.
- Keep tests deterministic: no real network calls, no real external services, no host application assumptions, and no writes outside Testbench temp paths or repository-controlled fixtures.
- Make `composer test` and `composer lint` the standard local verification commands.

## Current State

- `composer.json` already includes `pestphp/pest`, `pestphp/pest-plugin-laravel`, `orchestra/testbench`, `mockery/mockery`, and scripts for `composer test` and `composer lint`.
- `phpunit.xml` defines `Feature` and `Unit` suites and uses a `testing` SQLite connection.
- `tests/TestCase.php` registers Livewire, Sanctum, Spatie Permission, and `RedotServiceProvider`, loads package migrations, and enables `RefreshDatabase`.
- Initial Pest coverage now exists under `tests/Feature/Core`, `tests/Unit/Core`, and `tests/Unit/Packages`.
- `tests/TestCase.php` writes compiled Blade views to `/tmp/redot-core-tests/views` so tests do not create runtime artifacts under repository `storage/`.
- `AGENTS.md` is stale on this point because it says there is no committed test suite; update it to reflect the Pest/Testbench suite.

## Completed So Far

- [x] Added harness smoke tests for Testbench boot, merged config, package migrations, container aliases, and registered artisan commands.
- [x] Added root package unit coverage for `Setting`, `Language`, `LanguageToken`, `Union`, selected helpers, `Phone`, and `Captcha`.
- [x] Added root package feature coverage for `Localization` and the non-rebuild path of `EnsureDependenciesBuilt`.
- [x] Added package unit coverage for datatable column basics, `LangExtractor`, sidebar item/sidebar basics, and toastify session/view behavior.
- [x] Added auth package coverage for `AuthContext` and `RedotAuthManager` context resolution/error handling.
- [x] Added datatables package coverage for actions, action groups, and core string/number/select/ternary/date filters.
- [x] Added static integrity coverage for PSR-4 autoloading, root command naming, and package view resolution.
- [x] Reorganized tests by package boundary under `Core/` and `Packages/<PackageName>/`.
- [x] Added regression fixes discovered by tests:
  - `Setting` now invalidates nested cache keys when parent settings change.
  - `parse_csv()` now reindexes filtered values.
  - `LangExtractor` now honors constructor extensions and works when no fallback language files exist.
- [x] Updated `AGENTS.md` so testing guidance reflects the committed Pest/Testbench suite.
- [x] Verified the current suite with `composer test` and `composer lint`.

## Test Harness Work

1. [x] Add smoke tests for the current harness.
   - Assert Testbench boots with `RedotServiceProvider`.
   - Assert package migrations run and create `settings`, `languages`, `language_tokens`, `login_tokens`, and permission tables.
   - Assert config values from `config/redot.php` are merged.
   - Assert container aliases such as `sidebar` resolve.

2. [~] Add shared test helpers and fixtures.
   - Create test models for traits and datatables under `tests/Fixtures`.
   - Create temporary route files, Blade views, translation files, upload directories, and generated stub targets under Testbench paths.
   - [x] Add inline fixtures for datatable filters and auth context coverage.
   - [ ] Add reusable factories or builders for `LoginToken`, users, roles, and permissions.
   - [x] Add HTTP fakes for captcha verification.
   - [ ] Add reusable fakes for storage, queues, notifications, mail, cache, and events where behavior crosses Laravel boundaries.

3. [~] Stabilize package path assumptions.
   - Verify tests never depend on files that only exist in a consuming app unless the test creates fixture files first.
   - [x] Use `/tmp/redot-core-tests/views` for compiled Blade views instead of repository `storage/`.
   - [x] Use test-defined routes for localization and auth context coverage.
   - [ ] Expand temp-directory coverage for uploads, generated files, and language publishing.

## Priority Order

1. Service provider bootstrapping, config, migrations, helpers, and validation rules.
2. Middleware and routing behavior because those can break consuming applications broadly.
3. Auth package flows and route registrars.
4. Datatables package APIs, filters, columns, serialization, rendering, and export adapters.
5. Commands, jobs, and filesystem-heavy behavior.
6. Lower-risk presentational helpers: sidebar and toastify.
7. Stub generation and static repository integrity checks.

## Root Package Coverage

### Service Provider

- `RedotServiceProvider::register`
  - [ ] Registers publishable config, stubs, and migrations with the expected tags.
  - [x] Registers every artisan command listed in the provider.
  - [x] Registers `Sidebar` as a singleton and aliases it as `sidebar`.
  - [x] Registers auth, datatables, lang extractor, and toastify service providers.

- `RedotServiceProvider::boot`
  - Adds Redot metadata to `about`.
  - Registers Blade directives and component namespaces.
  - Sets default pagination view.
  - Configures the `api` rate limiter by authenticated user ID or IP.
  - Preserves empty strings on settings `PUT` requests while keeping Laravel's default null conversion elsewhere.
  - Prohibits destructive commands only in production.
  - Loads locales from the database when available and falls back to `config('redot.locales')`.
  - [x] Registers `phone` and `captcha` validator extensions indirectly through rule coverage.
  - Configures JSON cast encoding and decoding with unicode and slash preservation.

### Application Bootstrap

- `Redot\Application::configure`
  - Loads website API, dashboard API, global, website, dashboard, and fallback route groups when enabled.
  - Honors `redot.features.*.enabled` and dashboard prefix settings.
  - Applies locale URL prefix only when `redot.routing.append_locale_to_url` is enabled.
  - Registers web, dashboard, and API middleware in the expected order.
  - Renders API exceptions as JSON for `expectsJson()` and `api/*` requests.
  - Avoids JSON rendering for normal web requests.

### Helpers

- Cover every function in `src/helpers.php`.
- [~] Include happy paths, missing config/model data, null inputs, translated data, URL generation, asset hashing, API exception formatting, settings lookup, and fallback behavior.
- [x] Add regression tests for `parse_csv()` reindexing and API exception formatting.

### Models And Casts

- `Setting`
  - [x] Defaults from `config('redot.settings')`.
  - [x] Array and translated values.
  - [x] Validation rule shape for dashboard settings.
  - Missing setting fallback behavior.

- `Language`
  - [x] Locale code/name mapping.
  - [x] RTL flag handling.
  - Interactions with service provider locale bootstrapping.

- `LanguageToken`
  - [x] Fillable/cast behavior.
  - Key uniqueness and locale values.
  - Sync/extract/publish/revert job interactions.

- `LoginToken`
  - Token creation, expiry, consumption, and user relation behavior.
  - Invalid, expired, and already-used token paths.

- `Union` cast
  - [x] Casts primitive and structured values correctly.
  - [~] Handles null, arrays, JSON strings, invalid input, and model serialization.

### Validation Rules

- `Phone`
  - [x] Valid international and local phone formats.
  - [~] Invalid number, unsupported country, empty value, malformed value, and custom country parameters.

- `Captcha`
  - [x] Successful verification using HTTP fakes.
  - [~] Failed verification, missing secret key, missing token, malformed API response, network exception, and disabled/empty configuration behavior.

### Middleware

- `Localization`
  - [x] Sets locale from route parameter.
  - [x] Falls back to default locale.
  - [x] Rejects or redirects unsupported locales according to config.
  - Handles RTL locale metadata.
  - [x] Preserves intended URL/query string behavior.

- `EnsureDependenciesBuilt`
  - [x] Allows requests when dependencies are built.
  - Blocks or redirects when required build artifacts are missing.
  - Skips behavior for expected environments or paths if applicable.

- `RoutePermission`
  - Allows users with required permissions.
  - Denies guests and users without permissions.
  - Handles dashboard routes with missing route names.
  - Works with Spatie Permission cache and guard names.

### Controllers

- `FallbackController`
  - Redirects non-locale URLs when configured.
  - Returns proper fallback responses for website, dashboard, API, and unknown routes.
  - Preserves query strings and method expectations.

### Traits

- `CanUploadFile`
  - Uploads valid files to the configured disk/path.
  - Deletes replaced files.
  - Handles nullable uploads, missing paths, invalid files, image optimization hooks, and cleanup on model deletion.

- `Taggable`
  - Stores and retrieves tags.
  - Handles empty, duplicate, translated, and normalized tag input.
  - Supports querying/filtering by tags if the trait exposes scopes.

- `RespondAsApi`
  - JSON success and error response shapes.
  - Status codes, headers, validation errors, and exception payloads.

- `UserAuditable`
  - Sets created/updated/deleted user IDs when authenticated.
  - Handles guest operations.
  - Works with soft deletes if supported by the host model.

## Commands Coverage

Use Pest feature tests with Artisan, fake filesystem paths, and isolated fixtures.

- `BuildDependenciesCommand`
  - Builds expected dependency artifacts.
  - Reports success and failure clearly.
  - Handles missing package manager, failed process, and already-built state.

- `ClearUploadsCommand`
  - Removes only intended upload files.
  - Preserves configured keep paths and unrelated storage files.
  - Handles empty storage and missing directories.

- `EntityMakeCommand`
  - Generates the expected model/controller/request/resource/view/migration files.
  - Handles existing files, force options, namespaces, pluralization, and invalid names.

- `ExtractLanguageTokensCommand`
  - Dispatches or runs extraction.
  - Honors locale/config options.
  - Handles no tokens, duplicate tokens, and invalid source paths.

- `LintCommand`
  - Invokes configured lint command.
  - Reports non-zero exit codes.
  - Does not require a real host app.

- `ModelPopulateCommand`
  - Populates model data from supported input.
  - Handles invalid model classes, guarded attributes, casts, and relationships.

- `PublicLinkCommand`
  - Creates or reports public storage links.
  - Handles existing links and unsupported filesystems.

- `PublishLanguageTokensCommand`
  - Publishes token files for all configured locales.
  - Handles empty token sets and overwrite behavior.

- `RevertLanguageTokensCommand`
  - Reverts published tokens back to database values or source state.
  - Handles missing published files.

- `SyncLanguageTokensCommand`
  - Syncs extracted tokens to the database.
  - Handles create, update, delete, duplicate, and dry-run paths if available.

- `SyncPermissionsCommand`
  - Creates roles and permissions from route definitions.
  - Handles removed routes, duplicate permissions, guard names, and Spatie cache resets.

- `ViewMakeCommand`
  - Generates views from `stubs/`.
  - Handles dashboard, website, create, edit, show, index, datatable variants.
  - Preserves existing files unless force is supplied.

## Jobs Coverage

- `ExtractLanguageTokens`
  - Scans Blade/PHP/JS sources for translation tokens.
  - Ignores vendor, storage, cache, and configured excluded paths.
  - Handles nested directories, duplicate tokens, multiline calls, and malformed files.

- `SyncLanguageTokens`
  - Persists discovered tokens idempotently.
  - Removes or marks stale tokens according to intended behavior.
  - Preserves existing translations.

- `PublishLanguageTokens`
  - Writes language files for every configured locale.
  - Creates missing directories.
  - Uses deterministic ordering.
  - Handles write failures.

- `RevertLanguageTokens`
  - Restores database values from published language files.
  - Handles missing locale files, invalid PHP arrays, and partial locale data.

## Auth Package Coverage

### Service Provider And Manager

- `RedotAuthServiceProvider`
  - Registers config, routes, actions, middleware, facades, and bindings.
  - Supports overriding action implementations through the container.

- `RedotAuthManager` and `AuthContext`
  - [x] Resolve guards, brokers, providers, routes, and context values.
  - [~] Handle dashboard and website auth contexts separately.
  - [x] Fail clearly for invalid contexts.

### Actions

- `Login`
  - Valid credentials, invalid credentials, locked users, rate limiting, remember-me behavior, session regeneration, and intended redirect.

- `Logout`
  - Session logout, token logout if supported, session invalidation, CSRF regeneration, and redirect/JSON responses.

- `Registration`
  - User creation, validation, password hashing, events, auto-login, duplicate email, and disabled registration if configurable.

- `PasswordReset`
  - Send reset link, throttle, invalid email, valid reset, invalid token, expired token, password confirmation, and broker failures.

- `EmailVerification`
  - Send notification, signed verification URL, valid verification, invalid signature, already verified users, and throttling.

- `MagicLink`
  - Token generation, notification sending, valid login, invalid token, expired token, consumed token, and redirect behavior.

- `Lock`
  - Lock current session/user, unlock with valid credentials, reject invalid credentials, and respect middleware behavior.

### Routes And Middleware

- Route registrar classes for login, logout, registration, password reset, email verification, magic link, and lock routes.
- `Locked` middleware for locked and unlocked users, guest users, multiple guards, route exclusions, and redirects.
- `RateLimitsRequests` concern using Laravel rate limiter keys and decay settings.
- `QueriesUsers` concern for email, ID, provider, guard, and missing user behavior.

## Datatables Package Coverage

### Service Provider And Routes

- `DatatablesServiceProvider`
  - Publishes/merges config.
  - Loads views, translations, assets, and routes.
  - Registers commands and dependencies.

- `routes/datatable.php`
  - Search, filter, pagination, export, and action endpoints.
  - Authorization, validation, missing resource, and invalid column responses.

### Datatable Core

- `Datatable`
  - Resource definition, query building, column registration, action registration, filter registration, search, sorting, pagination, per-page limits, and serialization.
  - Relationship loading and aggregate columns.
  - Empty state and invalid configuration behavior.

### Columns

- `Column`
  - [x] Label, key, sortable/searchable flags, visibility, export behavior, and HTML escaping.

- `TextColumn`, `NumericColumn`, `DateColumn`, `ColorColumn`, `IconColumn`, `StatusColumn`, `TagsColumn`, `TernaryColumn`
  - Formatting, null values, custom callbacks, localization, CSS classes, export values, and invalid values.

### Filters

- Base `Filter`
  - [~] Attributes, labels, default values, query application, serialization, and reset behavior.

- `StringFilter`
  - [~] Contains, exact, starts-with, empty, and case behavior.

- `NumberFilter`
  - [~] Equals, range, min, max, invalid numeric input, and null handling.

- `DateFilter`
  - [~] Date equality, ranges, timezone boundaries, invalid dates, and null handling.

- `SelectFilter`
  - [~] Single and multiple selection, missing options, translated labels, and invalid values.

- `TernaryFilter`
  - [~] True, false, all, null, and custom query callbacks.

- `TrashedFilter`
  - With, only, and without trashed records on soft-deleting models.

### Actions And Traits

- `Action` and `ActionGroup`
  - [x] Labels, icons, URLs, methods, confirmation metadata, visibility callbacks, grouping, and attribute building.

- `BuildAttributes`
  - HTML attribute merging, escaping, boolean attributes, class merging, and data attributes.

- `InteractsWithRelations`
  - Nested relation paths, missing relations, eager loading, sorting, searching, and null-safe traversal.

- `Serializable`
  - Array and JSON output stability for columns, filters, actions, and datatable state.

### Views, Assets, And Exports

- Blade views render without errors for:
  - Main datatable view.
  - Filters.
  - Table, search, pagination, per-page selector, export, refresh, empty state, and actions.
  - PDF default view.

- PDF adapters:
  - `DomPdf` and `LaravelMpdf` dependency-present and dependency-missing paths.
  - Generated response headers, file names, and HTML input.
  - `MissingDependencyException` behavior.

- Command:
  - `DatatableMakeCommand` generates expected datatable class from its stub, handles existing files, force, namespaces, and invalid names.

## Lang Extractor Package Coverage

- `LaravelLangExtractorServiceProvider`
  - Registers command and any config/resources.

- `LangExtractor`
  - [x] Extracts translation keys from supported syntaxes.
  - [~] Handles Blade, PHP, nested directories, ignored paths, duplicate keys, dynamic keys, multiline strings, escaped quotes, and empty files.
  - [x] Produces deterministic merged output.

- `LangExtractCommand`
  - Runs extraction from CLI.
  - Handles configured paths, output paths, locale selection, overwrite behavior, and invalid input.

## Sidebar Package Coverage

- `Sidebar`
  - Adds, removes, sorts, groups, and retrieves items.
  - Handles active state, permissions, visibility callbacks, nested items, URLs, route names, icons, badges, and serialization.
  - Confirms singleton behavior through the container.

- `Item`
  - [~] Constructor defaults, fluent setters, child item handling, active matching, authorization callbacks, and array output.

## Toastify Package Coverage

- `LaravelToastifyServiceProvider`
  - Merges/publishes config.
  - Loads views and helpers.

- `Toastify`
  - [~] Queues success, error, warning, info, and custom toasts.
  - [x] Stores messages in session.
  - Handles options, durations, positions, duplicate messages, and clearing.

- Helpers and views
  - [x] Helper functions call the service correctly.
  - [x] CSS and JS Blade views render with default and custom config.

## Database And Migration Coverage

- [x] Every migration runs on SQLite in memory.
- Tables have expected columns, indexes, nullable fields, defaults, and foreign keys where applicable.
- [x] Spatie permission tables are compatible with package permissions.
- [x] Migrations are idempotent under Testbench refresh.
- [~] Models can create, update, delete, and query records using the migrated schema.

## Stubs And Generated Code Coverage

- Validate all files under `stubs/` are syntactically usable after placeholder replacement:
  - `dashboard.index.stub`
  - `dashboard.index-datatable.stub`
  - `dashboard.create.stub`
  - `dashboard.edit.stub`
  - `dashboard.show.stub`
  - `website.page.stub`

- For each generator command:
  - Generate into a temp Testbench app path.
  - Assert expected files exist.
  - Assert generated PHP files pass syntax checks.
  - Assert generated Blade files render with minimal fixture data when practical.
  - Assert generated routes, view names, namespaces, and model references match Laravel conventions.

## Static Integrity Tests

- [x] Assert all classes in `composer.json` PSR-4 namespaces autoload.
- [~] Assert service providers referenced in code exist and can be instantiated.
- [x] Assert command classes extend Laravel command base classes and define signatures.
- [x] Assert views referenced by code exist.
- Assert config keys referenced by code exist in default config.
- Assert translation files contain the same keys for `en` and `ar`.
- Assert no source file writes to `storage/` or app paths during package boot.

## External Dependency Strategy

- Use Laravel fakes for mail, notifications, queue, events, storage, HTTP, and cache.
- Mock process execution for lint/build commands unless the test explicitly verifies integration behavior.
- Fake captcha verification HTTP responses.
- Avoid requiring DomPDF or mPDF packages unless testing dependency-missing paths; dependency-present adapter tests can be conditional or use lightweight mocks.
- Avoid running Node, npm, composer, or host application commands from tests.

## Coverage Metrics

- Phase 1 target: at least smoke coverage for every service provider, model, middleware, validation rule, helper file, and package facade/API.
- Phase 2 target: branch coverage for all commands, jobs, auth actions, datatable filters, datatable columns, and route registrars.
- Phase 3 target: regression tests for every bug fixed after this plan is adopted.
- Track coverage by package area rather than only global percentage because this repository contains multiple independent subpackages.

## Current Test Layout

```text
tests/
  Feature/
    Core/
      Middleware/
      ServiceProviderTest.php
  Unit/
    Core/
      Casts/
      HelpersTest.php
      Models/
      Rules/
    Packages/
      Datatables/
      LangExtractor/
      Sidebar/
      Toastify/
  Fixtures/
    Models/
    Datatables/
    Routes/
    Views/
    Files/
```

## Execution Checklist

1. [x] Run `composer install` if dependencies are missing.
2. [x] Add harness smoke tests and ensure `composer test` passes.
3. [ ] Add factories/fixtures needed by multiple areas.
4. [~] Implement root package tests.
5. [~] Implement auth package tests.
6. [~] Implement datatables package tests.
7. [~] Implement lang extractor, sidebar, and toastify tests.
8. [ ] Add command and stub-generation integration tests.
9. [x] Add static integrity tests.
10. [x] Run `composer test`.
11. [x] Run `composer lint`.
12. [x] Update `AGENTS.md` testing guidance to reflect the real Pest/Testbench suite once tests exist.

## Done Criteria

- `composer test` passes from a clean checkout.
- `composer lint` passes.
- New tests do not require a consuming Laravel app.
- Every public package surface listed above has at least one direct test.
- High-risk flows, including auth, localization, permissions, filesystem writes, language token publishing, and datatable query behavior, include happy-path and failure-path tests.
- Generated files and stubs are covered by syntax or render tests.
- The plan is revisited after each major feature addition or bug fix.
