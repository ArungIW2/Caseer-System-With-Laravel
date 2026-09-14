# Caseer System With Laravel

A production-oriented retail Point of Sale (POS) and store management system built with Laravel 13.

## Phase Status

### Phase 1 — Domain & Database Architecture ✅

Implemented:

- Multi-store foundation with `stores`
- Store-scoped users
- Product catalog: categories, brands, units, products
- Store-specific inventory balances
- Append-only inventory movement ledger
- Suppliers and purchasing documents
- Sales, sale items, and payments
- Cash register sessions
- Sale returns and return items
- Audit log foundation
- Eloquent domain models and relationships
- Database architecture and ERD documentation in `docs/architecture/database.md`

The inventory ledger is intentionally separated from the current inventory balance. Checkout and other stock-changing workflows should update both inside one database transaction with row locking.

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

The application is being developed incrementally, with the domain model and database foundation established before implementing the POS workflow.
