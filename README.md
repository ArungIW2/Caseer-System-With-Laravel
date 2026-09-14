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

### Phase 5 — Purchasing & Receiving ✅

Implemented:

- Store-aware purchasing workflow
- Purchase draft creation
- Supplier selection
- Purchase line items with quantity, unit cost, discount, and tax
- Automatic subtotal, discount, tax, and grand-total calculation
- Unique purchase document number generation
- Purchase list with store/status filtering and pagination
- Purchase detail screen
- Draft-only receiving workflow to prevent duplicate stock receipt
- Receiving integrates with `InventoryService`
- Purchase receipt movement references the purchase document number
- Product cost price updated to the latest received unit cost
- Transactional row locking around purchase receiving
- Role-based purchasing access
- Store scoping for non-global roles

### Phase 6 — POS & Checkout ✅

Implemented:

- Retail POS screen
- Product lookup by SKU, barcode, or product name
- Store-aware product availability and stock display
- Cart with quantity, item discount, and item tax
- Server-side authoritative product price
- Server-side stock validation
- Transactional checkout
- Inventory deduction through `InventoryService`
- Row locking during checkout
- Automatic invoice numbers
- Totals and change calculation
- Cash, card, QRIS, and transfer payments
- Payment record creation
- Sales history and printable receipt
- Role-based POS access and store scoping

### Phase 7 — Sales Returns & Refunds ✅

Implemented:

- Full/partial return workflow
- Return directly from completed sale
- `RET-*` return documents
- Remaining-quantity validation
- Refund calculation based on original line value
- Optional restocking through `InventoryService`
- Inventory movement references return document
- Transactional sale/item locking
- Store-aware authorization
- Return history/detail/printable document
- Return reason capture

### Phase 8 — Cash Register & Reporting ✅

Implemented:

- Cash register opening and closing
- Opening cash and physical closing cash
- Expected cash and over/short reconciliation
- Cash-session numbers (`CS-*`)
- One open session per store
- POS sales linked to active cash session
- Date/store-filtered operational reporting
- Sales, transaction, purchase, payment-method, top-product, and low-stock metrics

### Phase 9 — User Management, Audit & Administration ✅

Implemented:

- User management UI for `super_admin` and `owner`
- Create users
- Edit users
- Password management
- Role assignment: `super_admin`, `owner`, `manager`, `cashier`, `inventory_staff`
- Store assignment for store-scoped users
- Active/inactive user management
- Protection against deactivating the currently logged-in account
- Search, role/status filters, and pagination
- Reusable `AuditLogService`
- Audit records include user, store, event, target model, old/new values, IP address, user agent, and timestamp
- Audit log administration screen
- Audit log filtering by event and store
- Role-protected administration routes
- Dashboard navigation for user management and audit logs

User administration flow:

`Admin → Create/Edit User → Assign Role → Assign Store → Activate/Deactivate → Audit Record`

Audit flow:

`Administrative Action → AuditLogService → audit_logs → Filterable Audit History`

## Planned Modules

- Draft/hold POS transactions
- Split payments and advanced refund settlement
- Automatic audit logging across purchasing, inventory, POS, returns, and cash sessions
- Historical cost/profit accounting with cost snapshots
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

The application is being developed incrementally, with transactional inventory controls established before implementing progressively richer POS, returns, reporting, and administration workflows.
