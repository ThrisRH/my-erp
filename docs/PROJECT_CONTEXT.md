# My-ERP context

## Product

My-ERP is a Laminas MVC application for managing internal ERP operations. The current foundation is employee information, employee accounts, and role-based access control.

## Runtime

- Application: PHP 8.0 / Apache / Laminas MVC.
- Local URL: `http://my-working.erp-local.com.vn:8000` (hoặc `http://localhost:8000`).
- Web Port: `8000` (cấu hình qua biến `APP_PORT` trong `.env`).
- Database: MySQL 8.4 Docker service, exposed locally on port `3307` to avoid the existing MySQL service on port `3306`.
- Database name: `erp`; credentials are supplied locally through `.env` and documented in `.env.example`.

## Current data model

- `departments`: organizational units with optional hierarchy.
- `employees`: employee identity, contact, organization, and employment status.
- `accounts`: one login account per employee; passwords are stored only as hashes.
- `roles` and `account_roles`: extensible role-based access control. Initial roles: `admin`, `employee`.

## Requirement log

### 2026-09-30 — ERP foundation

Requested a database foundation for employee records and employee accounts. Implemented MySQL Docker infrastructure and the initial schema above. Authentication screens and account provisioning behavior are not yet defined.

### 2026-09-30 — Agent continuity

Requested a repository-level instruction file so future agents preserve architecture, coding style, design-system reuse, and update this context for every new requirement. Implemented `AGENTS.md` and this context file.

### 2026-10-15 — Ignore development/local configs & Docker files

- Configured `.gitignore` to exclude local/development configuration files (`config/autoload/local.php`, `config/autoload/*.local.php`, `config/autoload/*.local-development.php`, and `config/development.config.php`) to ensure dev-specific configurations are not committed or pushed to production.
- Excluded Docker infrastructure files (`Dockerfile`, `docker-compose.yml`, and `docker/`) from git tracking as production deployment uses a separate environment setup.
- Configured application web port to `8000` (mapped to container port 80 via `APP_PORT=8000`) to avoid conflicts with other local projects. Accessible at `http://my-working.erp-local.com.vn:8000`.


