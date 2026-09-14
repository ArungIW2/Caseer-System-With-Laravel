# Caseer System With Laravel

A production-oriented retail Point of Sale (POS) and store management system built with Laravel 13.

## Planned Modules

- Authentication and role-based access control
- Retail POS and checkout
- Product, category, brand, and unit management
- Inventory and stock movement ledger
- Suppliers and purchasing
- Sales, returns, and refunds
- Payments and receipts
- Dashboard and reporting
- Audit logging
- Multi-store readiness
- Automated tests
- Docker and CI/CD

## Stack

- Laravel 13
- PHP 8.3+
- PostgreSQL
- Redis
- Blade + Livewire
- Tailwind CSS
- Pest / PHPUnit
- Docker
- GitHub Actions

## Development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

The application is being developed incrementally, starting with the domain model and database foundation before the POS workflow.
