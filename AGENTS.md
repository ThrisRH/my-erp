# My-ERP engineering instructions

## Shared project context

Read `docs/PROJECT_CONTEXT.md` before changing code. It is the authoritative record of product scope, architectural decisions, and the current requirement baseline.

For every new user requirement, update `docs/PROJECT_CONTEXT.md` in the same change set before declaring the task complete. Record the requirement, decisions made, changed areas, and any open questions. Do not silently overwrite a prior decision; mark it superseded and explain why.

## Architecture and database

- This is a Laminas MVC (Zend Framework successor) ERP application running in Docker.
- Keep controllers thin. Put reusable business rules in services and inject dependencies through Laminas service configuration.
- Use the existing `Application` module conventions, PSR-4 autoloading, and PSR-12 code style.
- MySQL schema changes are versioned SQL scripts in `docker/mysql/init/`; use descriptive, ascending prefixes. Do not edit an initialization script that may already have run in a developer database; add a new migration instead.
- Never store real credentials in versioned files. Add placeholders only to `.env.example`.

## UI and reuse

- First inspect existing layouts, partials, styles, routes, controllers, and services before creating equivalents.
- Preserve the established design system once it exists: use shared layout, tokens, components, and interaction patterns instead of page-specific copies.
- Add a reusable layout, partial, service, or helper when the same structure or logic would reasonably serve more than one screen.
- Keep accessibility and responsive behavior intact when changing views.

## Verification

- Run the smallest relevant checks after each change: PHP linting, Composer validation, and Docker Compose validation for infrastructure changes.
- For database work, verify the schema from inside the running database container when feasible.
