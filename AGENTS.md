# Repository Guidelines

## Project Structure & Module Organization
`src/` contains the package code, organized by Laravel-style domains such as `Commands/`, `Http/`, `Jobs/`, `Models/`, `Rules/`, and `Traits/`. In-repo subpackages live under `src/packages/` (`auth`, `datatables`, `lang-extractor`, `sidebar`, `toastify`) and are PSR-4 autoloaded from `composer.json`. Package configuration lives in `config/redot.php`, and generator templates in `stubs/`. Treat `storage/` as runtime output, not source.

## Build, Test, and Development Commands
Run `composer install` to install dependencies for PHP 8.3+ and Laravel 13+. Use `composer lint` or `vendor/bin/pint` to apply the repository formatting rules before pushing. This repository is a library, not a standalone app, so there is no local web server command here; validate behavior through the consuming Laravel application when changing service providers, middleware, or stubs.

## Coding Style & Naming Conventions
Follow Laravel conventions and PSR-4 namespaces: classes use `StudlyCase`, methods and properties use `camelCase`, and config, view, and stub files use `snake_case` or dot-oriented Laravel naming where appropriate. Use 4-space indentation in PHP. Format with Laravel Pint using the preset from `pint.json`; keep concatenation spacing consistent and avoid manual style deviations that Pint will rewrite.

## Testing Guidelines
There is currently no committed `tests/` suite or PHPUnit/Pest configuration in this repository. At minimum, run `composer lint` and verify changes in a host Laravel app. If you introduce automated tests, place them under `tests/Feature/*Test.php` or `tests/Unit/*Test.php` and keep coverage focused on package behavior, especially commands, middleware, and service-provider bootstrapping.

## Commit & Pull Request Guidelines
Recent history follows Conventional Commit prefixes such as `feat:`, `fix:`, `refactor:`, and `chore:`. Keep commit subjects short and imperative, for example `fix: handle missing upload path`. Open PRs against `master`, include a concise description, note any config or migration impact, and list manual verification steps. Include screenshots only when changing rendered views or generated stubs. CI runs Pint on pull requests and may auto-commit formatting fixes, so lint locally first.

## Security & Configuration Tips
Do not commit secrets, environment-specific settings, or generated files from `storage/logs`. Preserve the proprietary package metadata in `composer.json` and `LICENSE`, and document any new config surface in `config/redot.php`.
