# My-ERP engineering instructions

## Shared project context

Read `docs/PROJECT_CONTEXT.md` before changing code. It is the authoritative record of product scope, architectural decisions, and the current requirement baseline.

For every new user requirement, update `docs/PROJECT_CONTEXT.md` in the same change set before declaring the task complete. Record the requirement, decisions made, changed areas, and any open questions. Do not silently overwrite a prior decision; mark it superseded and explain why.

## Architecture and database

- This is a Laminas MVC (Zend Framework successor) ERP application running in Docker.
- Use a simple MVC structure: each module's top-level controller belongs in `src/Controller` and its action handlers belong in a controller-named folder, e.g. `src/Controller/PersonalFinance/IndexAction.php`. Domain/data classes and SQL queries belong in `src/Model`, while view helpers belong in `src/View/Helper`. Controllers must extend Laminas/Zend MVC controller base classes such as `AbstractActionController`.
- Do not introduce controller or service factories for application modules. Keep controller construction framework-native and let a model build its local database dependency from the configured application settings.
- Use the existing `Application` module conventions, PSR-4 autoloading, and PSR-12 code style.
- MySQL schema changes are versioned SQL scripts in `docker/mysql/init/`; use descriptive, ascending prefixes. Do not edit an initialization script that may already have run in a developer database; add a new migration instead.
- Any future sample, demo, or QA data written to the database must belong exclusively to the administrator's employee account. Never seed employee-scoped transactions, wallets, budgets, or personal categories for another account, and always remove temporary QA records after verification.
- Never store real credentials in versioned files. Add placeholders only to `.env.example`.

## UI and reuse

- First inspect existing layouts, partials, styles, routes, controllers, and services before creating equivalents.
- Preserve the established design system once it exists: use shared layout, tokens, components, and interaction patterns instead of page-specific copies.
- Add a reusable layout, partial, service, or helper when the same structure or logic would reasonably serve more than one screen.
- Keep accessibility and responsive behavior intact when changing views.
- Personal Finance uses four server-rendered tabs (`overview`, `transactions`, `budget`, `categories`). Keep large data queries and views tab-scoped; put reusable sections in partial views. Transaction lists use SQL pagination and visible month dividers.
- Use the shared application shell and CSS tokens: restrained light blue with black/gray neutrals, a 15% sidebar and 85% main area on desktop, bounded content width, and responsive reflow for phones through screens above 2K. Avoid decorative badges and page-specific visual systems.
- Use the 8px spacing scale and the typography scale 10, 12, 14, 16, 20, 24, 32, 42, and 56px. Prefer fluid percentages with sensible min/max bounds over rigid page widths.
- Category icons must come from the single `PersonalFinance\View\Helper\CategoryIcons` SVG set, including the category creation picker. Use charts for trend comparisons and shared dialog patterns for short forms.
- Global preferences live in `public/js/app-preferences.js` and the shared layout. Preserve its `data-theme` light/dark tokens and bilingual `data-i18n` convention; new user-facing UI must add Vietnamese and English messages there (or its future replacement) rather than introducing a module-specific preference system.
- English is the source and fallback language. Author all visible UI wording, validation feedback, errors, notices, placeholders, titles, and accessibility labels in English first, and add a Vietnamese translation through the shared i18n catalog. Never use Vietnamese as a fallback. Code comments may be written in Vietnamese.
- Use the shared popup month/date pickers in `public/js/personal-finance.js` for Personal Finance date selection; do not reintroduce typing-oriented date/month controls. Wallets are employee-owned and must be validated before a transaction is saved; quick wallet presets must preserve the automatic unique suffix behavior.

## Verification

- Run the smallest relevant checks after each change: PHP linting, Composer validation, and Docker Compose validation for infrastructure changes.
- For database work, verify the schema from inside the running database container when feasible.
