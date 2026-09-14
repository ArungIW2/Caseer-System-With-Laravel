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

### Phase 2 — Authentication & Authorization ✅

Implemented:

- Session-based login and logout
- Active-user validation
- Database-backed sessions
- User roles: `super_admin`, `owner`, `manager`, `cashier`, and `inventory_staff`
- Role middleware and protected routes
- Store-aware authenticated user foundation
- Default Super Admin seeder for local development
- Protected dashboard

### Phase 3 — Master Data Management ✅

Implemented:

- Store management for `super_admin` and `owner`
- Category CRUD with parent-category support
- Brand CRUD
- Unit CRUD
- Product CRUD with SKU/barcode uniqueness and catalog relationships
- Supplier CRUD
- Validation rules for master data
- Search and pagination
- Soft delete for categories, brands, units, products, and suppliers
- Dependency checks before destructive deletion
- Role-based access for master-data modules
- Dashboard navigation to master-data modules
- Development seed data for a store, categories, brands, units, supplier, and demo product

### Phase 4 — Inventory Management ✅

Implemented:

- Store-specific inventory balance UI
- Stock In with optional unit cost
- Stock Out with available-stock validation
- Manual stock adjustment to an exact target balance
- Stock opname / physical-count reconciliation
- Transfer stock between stores
- Append-only inventory movement ledger UI
- Movement filtering and pagination
- Minimum-stock monitoring and low-stock filter
- Database transactions around every stock-changing operation
- `lockForUpdate()` row locking to protect concurrent stock changes
- Deterministic lock ordering for inter-store transfers to reduce deadlock risk
- Reserved quantity respected when issuing or transferring stock
- Negative stock protection
- Store-aware access policies for inventory screens

Inventory changes update the current balance and append a movement record inside the same transaction. Future purchasing, POS, returns, and transfer workflows should reuse `InventoryService` rather than modifying balances directly.

## Planned Modules

- User management UI and role administration
- Purchasing workflow integrated with inventory receiving
- Retail POS and checkout integrated with inventory locking
- Sales, returns, and refunds
- Payments and receipts
- Dashboard and reporting
- Audit logging integration
- Automated feature/unit tests
- Docker and CI/CD
- Production hardening

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
php artisan migrate --seed
npm install
npm run dev
```

### Local development account

The development seeder creates:

- Email: `admin@caseer.test`
- Password: `ChangeMe123!`
- Role: `super_admin`

Change or remove this credential before any non-local deployment.

The application is being developed incrementally, with transactional inventory controls established before implementing the POS checkout workflow.
