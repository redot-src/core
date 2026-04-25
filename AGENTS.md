# Repository Guidelines

## Project Structure & Module Organization
`src/` contains the package code, organized by Laravel-style domains such as `Commands/`, `Http/`, `Jobs/`, `Models/`, `Rules/`, and `Traits/`. In-repo subpackages live under `src/packages/` (`auth`, `datatables`, `lang-extractor`, `sidebar`, `toastify`) and are PSR-4 autoloaded from `composer.json`. Package configuration lives in `config/redot.php`, and generator templates in `stubs/`. Treat `storage/` as runtime output, not source.

## Build, Test, and Development Commands
Run `composer install` to install dependencies for PHP 8.3+ and Laravel 13+. Use `composer test` or `vendor/bin/pest` to run the Pest/Orchestra Testbench suite, and `composer lint` or `vendor/bin/pint` to apply the repository formatting rules before pushing. This repository is a library, not a standalone app, so there is no local web server command here; validate behavior through the consuming Laravel application when changing service providers, middleware, or stubs that are not covered by package tests.

## Coding Style & Naming Conventions
Follow Laravel conventions and PSR-4 namespaces: classes use `StudlyCase`, methods and properties use `camelCase`, and config, view, and stub files use `snake_case` or dot-oriented Laravel naming where appropriate. Use 4-space indentation in PHP. Format with Laravel Pint using the preset from `pint.json`; keep concatenation spacing consistent and avoid manual style deviations that Pint will rewrite.

## Testing Guidelines
The repository has a Pest suite backed by Orchestra Testbench. Keep root package tests under `tests/Feature/Core` or `tests/Unit/Core`, and bundled package tests under `tests/Unit/Packages/<PackageName>` or `tests/Feature/Packages/<PackageName>` when feature-level package tests are needed. Prefer package-level fixtures and Laravel fakes over host-app assumptions; compiled views and temp artifacts should stay outside repository `storage/`. Run `composer test` and `composer lint` before pushing, and still verify behavior in a host Laravel app when changing service providers, middleware, stubs, or workflows not covered by automated tests.

## Commit & Pull Request Guidelines
Recent history follows Conventional Commit prefixes such as `feat:`, `fix:`, `refactor:`, and `chore:`. Keep commit subjects short and imperative, for example `fix: handle missing upload path`. Open PRs against `master`, include a concise description, note any config or migration impact, and list manual verification steps. Include screenshots only when changing rendered views or generated stubs. CI runs Pint on pull requests and may auto-commit formatting fixes, so lint locally first.

## Security & Configuration Tips
Do not commit secrets, environment-specific settings, or generated files from `storage/logs`. Preserve the proprietary package metadata in `composer.json` and `LICENSE`, and document any new config surface in `config/redot.php`.
