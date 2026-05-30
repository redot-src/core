# Redot Core

Core package for the Redot Dashboard. It bundles five focused packages on top of
a shared foundation of helpers, models, traits, casts, and validation rules:

- **Auth** — drop-in authentication actions, routes, and customization hooks.
- **Datatables** — server-driven tables with columns, filters, row actions, and PDF export.
- **Sidebar** — composable navigation for dashboard layouts.
- **Toastify** — flash and session-driven toast notifications.
- **Lang Extractor** — extract translation keys from your codebase.

## Requirements

- PHP 8.3+
- Laravel 13+
- Livewire 4.2+

## Documentation

Full usage documentation lives in [`docs/`](docs).

## Testing

This package uses Pest with Orchestra Testbench.

```bash
composer install
composer test
```

The testbench schema mirrors the package-owned tables used by Redot Dashboard, so package tests run against the same shape the dashboard app expects.

## License

This package is proprietary and intended for use only within the paid Redot Dashboard.
See `LICENSE` for details.
