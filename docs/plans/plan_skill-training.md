# Skill Training Feature Plan (Draft)

## Request

Add a skill-training page where the signed-in user chooses knowledge keywords and a level (`basic` or `advance`), starts a practice session, and receives short SQL theory questions or exercises at a configurable random interval. Gemini generates questions and evaluates submitted answers. Each attempt is persisted for progress reporting.

## Current behavior and project touchpoints

- My-ERP uses Laminas MVC modules, server-rendered views, MySQL migrations, and authenticated routes guarded by `Application\Module` ACL rules.
- Account records are linked to employee records, but this feature's practice history must be scoped to the signed-in account, not shared through an employee or client-provided identifier.
- The application already has a `GEMINI_API_KEY` environment placeholder in `.env.example`; no Gemini integration was found in the application modules. The real key must remain server-side and must not be read into or returned to the browser.
- A new route needs an explicit permission mapping because unknown protected routes are denied by default.
- The browser Notification API can notify while the web app is open and permission has been granted. It cannot reliably run the interval or deliver notifications after the browser is closed without a separately designed push/scheduler service.

## Clarified requirements

- Each account sees and manages only its own training sessions and attempts.
- A user configures a minimum and maximum interval in minutes. Each next notification is scheduled at a random interval in that inclusive range.
- Each question is one type only: Gemini chooses theory or exercise independently for each round.
- Training targets MySQL 8.4, matching the application's database engine. User keywords are focus hints, not a restriction that can hide the rest of the selected level's curriculum. The catalog is versioned and uses granular topic IDs across:
  - Query basics: SELECT, DISTINCT, aliases, WHERE, comparison/logical operators, IN, BETWEEN, LIKE, IS NULL, CASE, ORDER BY, LIMIT/OFFSET, UNION/UNION ALL.
  - Joins and aggregation: INNER/LEFT/RIGHT/CROSS/SELF joins, multi-table joins, COUNT/SUM/AVG/MIN/MAX, GROUP BY, HAVING.
  - Nested query features: scalar/EXISTS/correlated/derived subqueries, CTE and recursive CTE, window functions with OVER/PARTITION BY and ranking/offset functions.
  - DML: INSERT VALUES/SELECT, UPDATE, DELETE, REPLACE, upsert with ON DUPLICATE KEY UPDATE.
  - DDL/schema: CREATE/ALTER/DROP/TRUNCATE/RENAME for databases/tables/views/indexes; data types, defaults, and constraints.
  - Transactions/concurrency/security: START TRANSACTION, COMMIT, ROLLBACK, SAVEPOINT, isolation, locks, user/role privileges, GRANT/REVOKE.
  - Functions and performance: numeric/string/date/null/JSON functions; indexes (single/composite/covering), EXPLAIN, query plans, sargability, and query optimization.
  - Advanced programmable SQL: procedures/functions, triggers, events, cursors, and handlers.
  Do not claim coverage of non-SQL administrative APIs or MySQL features outside the catalog; the versioned catalog is the explicit coverage boundary.
- Questions should be short and focused, drawn across the catalog in shuffled/weighted order. Exercise prompts contain one task; theory prompts test one concept. Store a validated topic ID, statement family/keyword tags, and complexity tier with every attempt.
- The user chooses `basic` or `advance` and supplies training keywords before starting.
- The response deadline is configurable in seconds (default: 40 seconds). The deadline starts when the notification is dispatched. At the deadline:
  - If the answer field is empty, record the unanswered attempt and end the session.
  - If any answer text has been entered, mark the attempt as late but allow the user to finish and submit it; do not auto-submit.
- Answers submitted after the deadline are accepted and evaluated, with actual elapsed time and late status recorded.
- Closing the page should end the session from the user's perspective. The page should send a best-effort stop request on `pagehide`; because browsers may terminate before the request completes, a server-side heartbeat/expiry check should mark stale sessions stopped when next observed. No notifications are expected while the page is closed.
- If Gemini fails or returns invalid data, retain the submitted attempt, do not add an accuracy result, and let the user retry evaluation without losing the session.
- Concepts may recur, but topic selection must be randomized/weighted rather than following the user's keyword order. Never repeat the same normalized question for the same account across sessions; a concept may recur only with meaningfully different wording/scenario and changed exercise identifiers/values.
- Supply account-wide recent question history to Gemini, reject generated questions whose normalized wording fingerprint already exists for the account, and enforce this exact-question no-repeat rule with an account/fingerprint database unique key. Semantic variation is prompt-guided; the database guarantees exact normalized text is not repeated.
- Basic covers query foundations (projection, filtering/operators, ordering/limit, aggregates/grouping, simple two-table joins), simple INSERT/UPDATE/DELETE, CREATE TABLE basics, and common types/constraints. Advance includes all catalog topics, including joins across more than three tables, nested subqueries/CTEs/window functions, complex predicates, DDL changes/drops, transactions/isolation/locking, privilege statements, indexes/EXPLAIN, programmable SQL, and performance/design reasoning.
- Adapt question selection per account and granular topic using the last 10 evaluated attempts for that topic. Require at least 5 evaluated attempts before classifying a topic as mastered/weak. Use deterministic topic weights for testability: under 5 attempts = exploration weight 3; accuracy <60% = weight 5 and lower/steadier complexity; 60-79% = weight 2 and hold complexity; >=80% = weight 1 and raise complexity within the selected basic/advance tier. Draw topics using weighted random selection; keyword matches may add preference but cannot suppress unexplored curriculum topics. Existing global notification interval and response-time settings remain unchanged.
- Progress should show daily attempts and percentage change versus the previous day, average response speed, daily accuracy, and overall accuracy. Use the reporting timezone consistently and define zero-denominator states clearly.
- Accuracy means the percentage of evaluated answers with a score of at least 80/100. Show `N/A` when there are no evaluated answers.
- Interval bounds must be between 1 and 120 minutes, inclusive; response duration must be between 5 and 600 seconds, inclusive. Defaults are 10-30 minutes and 40 seconds.
- Start the next random interval only after the current answer is submitted. Never dispatch a question while the prior answer is awaiting evaluation.
- Use MySQL syntax in SQL questions and exercises.
- Grade SQL answers by logical correctness, not capitalization: SQL keywords may be uppercase, lowercase, or mixed case without penalty. Do not require formatting or an optional trailing semicolon; penalize only meaningful logic or syntax errors that change the answer.
- All generated exercise table and column identifiers must be English snake_case even when the question is Vietnamese (for example `employee` and `employee_id`, never localized names such as `nhan_vien`).
- Before starting a session, the user selects English or Vietnamese as the training language. The chosen language is stored with the session and used for the question, verdict, and explanation; the answer may be written in either language.
- English is the source language; add Vietnamese translations through the shared localization catalog. Use the existing application shell and shared design tokens.

## Proposed implementation

### MVC and access control

- Add a dedicated Skill Training module/page and its controller/action handlers, model, and views following existing Laminas conventions.
- Register the page and its JSON/form actions in routing and add explicit ACL permissions in `Application\Module`; permit each account to access only its own session and attempt records.
- Add a navigation entry in the shared application layout, subject to the feature permission.
- Use CSRF validation for state-changing requests, consistent with the existing application guard.

### Persistence

- Add a new ascending SQL migration; do not edit prior initialization scripts.
- Keep session records account-owned and continue using the existing MySQL 8.4 database; this feature does not migrate database engines.
- Add attempt metadata for validated curriculum topic ID, statement family/keyword tags, complexity tier, and normalized question fingerprint. Keep score and answer history account-scoped.
- Derive account/topic rolling statistics from evaluated attempts. Under-sampled topics use the explicit exploration weight rather than being treated as failures; add a summary table only if query performance requires it.
- Ensure every read and write is scoped by the authenticated account ID. Add indexes for account/session/date reporting and foreign keys where compatible with the existing schema.
- Keep failed Gemini evaluations identifiable and retryable without duplicating an attempt.

### Session and notification flow

1. The setup page requests Notification permission only after a user action and explains that the tab must stay open.
2. Validate keywords, level, training language, minimum/maximum interval, and response duration. Require positive bounded numeric values and `minimum <= maximum`.
3. Starting a session persists configuration; the browser schedules a random interval within the selected range while the page remains active.
4. At each interval, choose an eligible topic using the account's adaptive weights, curriculum coverage, selected basic/advance scope, and shuffled keyword hints. The configured interval remains the user's chosen interval; adapt topic frequency by weighted selection within each round rather than silently changing the timer.
5. Request one short question from the backend. The backend constructs a constrained Gemini prompt from selected topic, level/language, required MySQL statement family/keywords, recent account-wide questions, desired complexity tier, and a structured response format that includes validated topic metadata.
6. Validate the returned topic ID, statement family, tier, question and expected answer against the catalog and size limits. Reject/retry invalid metadata or duplicate question fingerprints before saving.
7. Persist the question before displaying it, issue a concise macOS/browser notification, display the answer UI, and start the response timer at dispatch time.
8. Persist the answer and elapsed time before requesting Gemini evaluation. The evaluation prompt should return a numeric score, concise verdict, correctness rationale, and deeper explanation only when the answer reveals a misunderstanding. Explicitly ignore SQL keyword letter case/formatting and score logical correctness. Validate and safely parse model output before storing it.
9. On a blank answer at the deadline, persist an unanswered attempt and stop the session. On a non-empty answer, mark late and continue to accept submission. A submitted answer remains retryable if Gemini evaluation fails.
10. Stop the browser scheduler on explicit stop/pagehide. Record a heartbeat while active; treat stale sessions as stopped when subsequently accessed.

### Gemini integration and prompt constraints

- Read `GEMINI_API_KEY` only from backend environment/configuration; do not expose it in HTML, JavaScript, logs, or API responses.
- Use backend HTTP requests to Gemini; set bounded timeouts and handle provider/network errors without losing saved answers.
- Use distinct generation and evaluation prompts. Require exactly one question and either theory or exercise, never both. Include the selected catalog topic and complexity tier; require English snake_case identifiers and vary wording, schema names, values, and task framing from recent questions.
- Select only from the versioned MySQL 8.4 curriculum catalog and return its topic ID plus statement-family/keyword tags and complexity tier for server-side validation.
- Generate the question, verdict, and explanation in the selected session language (English or Vietnamese); accept learner answers in either language.
- Require English snake_case table/column identifiers in every generated exercise. In evaluation, explicitly ignore SQL keyword capitalization and formatting differences when judging logic.
- Require machine-readable structured output and validate fields, score bounds, and lengths server-side; treat model-provided text as untrusted display content.
- Confirm the exact Gemini model and API version against Google's currently supported API before implementation; do not hardcode a model unsupported by the configured key.

### Progress reporting

- Daily attempts: count submitted answers and unanswered timeout attempts; label totals clearly.
- Daily volume change: percentage change against the previous calendar day; show `N/A` when the previous day has zero attempts and avoid division by zero.
- Average response speed: mean elapsed seconds for submitted answers, including late answers; exclude unanswered attempts.
- Accuracy: evaluated answers with score at or above the agreed correctness threshold divided by evaluated answers; display daily and overall figures and show `N/A` when there are no evaluated answers.

## Data and privacy safeguards

- Scope all session/attempt queries to the authenticated account; never trust an account ID supplied by the browser.
- Store API credentials only in local environment configuration. Never include a real key in source control or test fixtures.
- Do not store more answer/question data than needed for retry, review, and progress statistics. The user should be able to inspect their own attempt history; cross-account access must be denied.
- Avoid persisting prompts containing credentials or unrelated personal data.

## Verification plan

- PHP lint for every added/changed PHP file and Composer validation if autoload/dependency configuration changes.
- Focused tests for ownership isolation, session configuration validation, interval bounds, timeout/late-answer transitions, score parsing, duplicate fingerprint rejection across sessions, catalog tag validation, weighted topic selection, rolling-window thresholds, difficulty progression, and daily statistics edge cases.
- Validate the new SQL migration against the running MySQL container when feasible; verify indexes, foreign keys, and account scoping.
- Exercise the browser flow: permission denied/granted, session start, notification and timer, on-time and late answer, blank timeout, explicit stop/pagehide, Gemini failure/retry, and responsive/light/dark/localized UI.
- Run Docker Compose validation only if deployment/infrastructure configuration changes.

## Confirmed decisions

1. Interval range: `1-120` minutes; response duration: `5-600` seconds. Defaults are `10-30` minutes and `40` seconds.
2. Schedule the next random interval after the current answer is submitted.
3. Use MySQL syntax for questions and exercises.
4. At the configured deadline an empty answer ends the session; a non-empty answer is marked late and remains editable/submittable.
5. Correct answers score at least 80/100.
6. User selects English or Vietnamese before starting; default to the current interface locale. Use the selected language for generated questions, verdicts, and explanations; learner answers may be in either language.
7. Keep MySQL 8.4 as the feature database and SQL dialect; no database-engine migration.
8. Cover all listed SQL statement families, including SELECT, DML, DDL, constraints, transactions, security/permissions, functions, indexes, EXPLAIN, and performance. Basic/advance scope is as described above.
9. Adapt topic weights using the last 10 evaluated attempts per topic after at least 5 attempts: under 5 = weight 3 exploration; >=80% = weight 1 and progress complexity; <60% = weight 5 and lower/steadier tier; 60-79% = weight 2 and hold tier.
10. Do not repeat normalized question text account-wide; concept repetition is allowed only with substantially changed wording/scenario/details.

## Approval gate

The original plan and prior approved additions were implemented. This expanded curriculum/adaptive-learning request is a new plan revision and is pending user review. Do not modify application code or schema until the user explicitly approves this revised plan. After approval, update `docs/PROJECT_CONTEXT.md` in the same implementation change set.