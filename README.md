# Redot Core

Core package for the Redot Dashboard.

## Requirements

- PHP 8.3+
- Laravel 13+
- Livewire 4.2+

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
