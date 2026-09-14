# Caseer System

> **Professional Retail Management & Point of Sale Platform**
>
> A portfolio-grade business application built with Laravel 13, demonstrating professional software engineering across retail operations, inventory control, purchasing, POS, financial reconciliation, RBAC, auditability, automated testing, CI/CD, and containerized deployment.

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/) [![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/) [![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/) [![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://www.docker.com/) [![CI](https://img.shields.io/badge/CI-GitHub%20Actions-2088FF?logo=githubactions&logoColor=white)](https://github.com/features/actions)

## Executive Summary

Caseer System is intentionally designed as a **real-world business system**, not a basic CRUD demonstration. Its architecture prioritizes domain rules, transactional integrity, data traceability, authorization, and operational reliability.

## Core Business Domains

| Domain | Capabilities |
| --- | --- |
| Product Catalog | Products, SKU/barcode, categories, brands, units, pricing, suppliers |
| Inventory | Stock in/out, adjustment, opname, transfers, movement ledger, low-stock monitoring |
| Purchasing | Purchase documents, suppliers, receiving, cost tracking |
| POS | Product search, cart, discounts, tax, checkout, payments, receipts |
| Sales Returns | Full/partial returns, refund calculation, optional restocking |
| Cash Register | Opening/closing sessions, expected cash, physical cash, variance reconciliation |
| Reporting | Sales, purchases, payment methods, top products, stock, gross profit |
| Administration | Users, roles, store assignment, account status |
| Audit | Business-event audit trail and traceability |

## Engineering Highlights

### Transactional Business Logic
Critical workflows use service-layer business logic and database transactions to preserve business invariants.

### Inventory Concurrency Control
Inventory operations use `lockForUpdate()`, negative-stock protection, reserved-quantity checks, and deterministic locking for transfers.

### Inventory Ledger
Current balances are supported by append-only inventory movements, providing traceability for receiving, issuing, adjustments, transfers, sales, and returns.

### Historical Financial Integrity
Each sale item snapshots `unit_cost`, allowing gross-profit reporting to use historical transaction cost rather than a changing product cost.

### Multi-Store Architecture
Products are global catalog entities while inventory and operational transactions are store-aware. Store-scoped roles require an assigned store.

### RBAC
- `super_admin` — platform administration
- `owner` — business administration and reporting
- `manager` — store management
- `cashier` — POS and cash operations
- `inventory_staff` — inventory operations

### Auditability
Structured audit records capture business events with user, store, target, old/new values, IP address, user agent, and timestamp.

## Implemented Modules

- **Authentication & Authorization** — session authentication, active-user checks, role middleware, store-aware authorization.
- **Master Data** — stores, categories, brands, units, products, suppliers, validation, search, pagination, soft deletion.
- **Inventory** — receiving, issuing, adjustments, stock opname, transfers, ledger, low-stock monitoring, concurrency protection.
- **Purchasing** — purchase drafts, suppliers, line items, cost/tax/discount calculation, transactional receiving.
- **Point of Sale** — SKU/barcode/name lookup, cart, authoritative pricing, stock validation, checkout, payments, invoices, receipts.
- **Sales Returns** — full/partial returns, remaining-quantity validation, refund calculation, optional restocking.
- **Cash Register & Reporting** — cash sessions, reconciliation, sales/purchase reports, payment analysis, top products, low stock, gross profit.
- **Administration & Audit** — user management, role/store assignment, activation, audit logs, security protections.

## Development Roadmap

| Phase | Scope | Status |
| --- | --- | :---: |
| 01 | Domain & Database Architecture | ✅ |
| 02 | Authentication & Authorization | ✅ |
| 03 | Master Data Management | ✅ |
| 04 | Inventory Management | ✅ |
| 05 | Purchasing & Receiving | ✅ |
| 06 | POS & Checkout | ✅ |
| 07 | Sales Returns & Refunds | ✅ |
| 08 | Cash Register & Reporting | ✅ |
| 09 | User Management & Audit | ✅ |
| 10 | Testing, Historical Accounting, CI/CD & Hardening | ✅ |
| 11 | Advanced POS & Operational Hardening | 🚧 |

### Planned Improvements

- Draft/hold POS transactions
- Split payments and advanced refund settlement
- Full audit coverage across critical domains
- Idempotent checkout protection
- Cash in/out adjustments
- Expanded integration and concurrency tests
- Nginx + PHP-FPM production profile
- Health checks, observability, and further security hardening

## Technology Stack

**Backend:** Laravel 13, PHP 8.3+, PostgreSQL 16, Redis  
**Frontend:** Blade, Livewire, Tailwind CSS, Vite  
**Engineering & DevOps:** PHPUnit, GitHub Actions, Docker, Docker Compose

## Engineering Practices Demonstrated

- Service-layer architecture
- Domain/business-rule modeling
- Database transactions
- Row-level concurrency control
- Multi-store data scoping
- RBAC and authorization boundaries
- Historical accounting snapshots
- Append-only operational ledgers
- Defensive validation
- Audit logging
- Feature testing
- PostgreSQL-based CI
- Automated frontend builds
- Containerized application delivery

## Project Structure

```text
app/                 # Controllers, middleware, models, services
database/            # Migrations and seeders
resources/            # Blade/Livewire views
tests/                # Feature and unit tests
docs/architecture/    # Database and architecture documentation
.github/workflows/    # CI/CD workflows
Dockerfile
docker-compose.yml
phpunit.xml
composer.json
package.json
```

## Getting Started

### Requirements

- PHP 8.3+
- Composer
- Node.js 22+
- PostgreSQL 16+
- Redis (recommended)
- Git

### Local Development

```bash
git clone https://github.com/ArungIW2/Caseer-System-With-Laravel.git
cd Caseer-System-With-Laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

### Docker

```bash
docker compose up --build
docker compose exec app php artisan migrate --seed
```

## Development Account

```text
Email    : admin@caseer.test
Password : ChangeMe123!
Role     : super_admin
```

> **Security:** change or remove this credential before any non-local deployment.

## Documentation

Database architecture and the ERD are documented in `docs/architecture/database.md`.

## Project Status

Caseer System is an **active portfolio project under continuous development**. Completed phases represent implemented capabilities; the roadmap identifies the next production-hardening priorities.

**Caseer System** — a professional portfolio project demonstrating software engineering from domain modeling and transactional business logic to testing and deployment readiness.
