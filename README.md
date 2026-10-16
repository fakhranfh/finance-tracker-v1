# Finance Tracker

A personal **finance tracker** built with **Laravel 13**. Track money across multiple accounts, record income, expenses, and transfers, organize them with categories, and work toward savings goals. Server-rendered Blade + Tailwind CSS pages call a session-based REST API via `fetch`, so all backend logic lives in the API layer rather than in page controllers.

## Features

- **Accounts**: create, edit, reorder, and bulk delete accounts, each with a color and icon. Per-account summary page with balance, monthly and yearly totals, and balance snapshots.
- **Records**: income, expense, and transfer records with filtering, search, and sorting. Summary totals and export to **XLSX** and **PDF**.
- **Record import**: multi-step import wizard (map columns, then map values) with progress saved in the database, so imports can be resumed and many can exist at once.
- **Categories**: income and expense categories to classify records.
- **Goals**: savings goals with a status (active, paused, reached), savings entries, and optional sub-goals with their own saved amounts.
- **Authentication**: login, logout, forgot and reset password, and email verification. Registration is only open while no user exists, which makes it a single-owner app.
- **Profile**: view and update the user profile. The timezone is detected from the IP address on login, and datetimes are shown in the user's timezone.
- **Multi-language**: English (`en`) and Indonesian (`id`), switchable via `/lang/{locale}`.
- **API documentation** with Swagger (L5-Swagger).
- **Local-only helpers**: dev auto-login and sample data generators (only enabled when `APP_ENV=local`).
- **Code quality**: Pest, Playwright (E2E), Larastan, and Laravel Pint.

## Tech Stack

| Area | Technology |
| --- | --- |
| Backend | PHP ^8.4, Laravel ^13.17, Laravel Sanctum |
| Frontend | Blade, Tailwind CSS 4, Vite 8 |
| Database | PostgreSQL (default in `.env.example`) |
| Export | maatwebsite/excel (XLSX), barryvdh/laravel-dompdf (PDF) |
| Geolocation | stevebauman/location (timezone detection) |
| API Docs | darkaonline/l5-swagger |
| Testing | Pest 5, Playwright |
| Static analysis | Larastan, Pint |

## Project Structure

```
app/
├── Console/Commands   # MakeManage, RemoveManage, RebuildAccountTotals, ReconcileAccountBalances, SeedE2eFixture, Version
├── Enums/             # RecordType, CategoryType, GoalStatus, RecordImportStep
├── Http/              # Page controllers, Api controllers, Middleware, Requests, Resources
├── Models/            # Account, Record, Category, Goal, SubGoal, GoalSaving, RecordImport, User
├── Repositories/      # One repository + interface per entity
├── Services/          # Business logic (account totals and snapshots, record import, goals, etc.)
└── Traits/            # Per-feature logic
routes/
├── web.php            # Blade pages
└── api.php            # Session-based API endpoints (web middleware + auth:sanctum)
lang/                  # en & id translations
tests/                 # Feature (Pest), Unit, and Playwright
```

## Installation

Prerequisites: PHP 8.4+, Composer, Node.js, and PostgreSQL.

```bash
git clone <repo-url> finance-tracker-v1
cd finance-tracker-v1
composer setup
```

`composer setup` installs dependencies, copies `.env`, generates the app key, runs migrations, and builds assets. Adjust `APP_NAME` and the database credentials (`DB_*`) in `.env` **before** migrating if needed.

## Running

```bash
composer dev
```

This starts the server (`php artisan serve`), the queue listener, and Vite together. The app is available at `http://localhost:8000`. On first visit, `/` redirects to registration; once a user exists it redirects to login.

## Routes

**Pages**: `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/dashboard`, `/profile`, plus index/create/edit pages for `/accounts`, `/records`, `/categories`, and `/goals`. Also `/accounts/{id}/summary`, `/records/import`, `/goals/{id}`, and `/goals/{goalId}/sub-goals`.

**API** (prefix `/api`, all except auth endpoints require `auth:sanctum`):

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/register`, `/login`, `/forgot-password`, `/reset-password` | Authentication (public) |
| POST | `/logout` | Sign out |
| GET/PUT | `/profile` | Profile |
| GET/POST/PUT/DELETE | `/accounts`, `/accounts/{id}` | Account CRUD |
| PUT | `/accounts/order` | Reorder accounts |
| GET | `/accounts/{id}/summary` | Account summary |
| GET/POST/PUT/DELETE | `/records`, `/records/{id}` | Record CRUD |
| GET | `/records/summary`, `/records/export/{xlsx\|pdf}` | Totals and export |
| GET/POST/PUT/DELETE | `/records/imports`, `/records/imports/{id}` | Import wizard state |
| POST | `/records/imports/{id}/values`, `/records/imports/{id}/run` | Map values and run an import |
| GET/POST/PUT/DELETE | `/categories`, `/categories/{id}` | Category CRUD |
| GET/POST/PUT/DELETE | `/goals`, `/goals/{id}` | Goal CRUD |
| GET/POST/PUT/DELETE | `/goals/{id}/savings` | Goal savings |
| GET/PUT | `/goals/{id}/sub-goals`, `/goals/{id}/sub-goals/{subGoalId}` | Sub-goals |
| DELETE | `/accounts`, `/records`, `/categories`, `/goals` | Bulk delete |
| POST/DELETE | `/dev/auto-login`, `*/generate`, `/accounts/{id}/records` | `local` environment only |

The full, interactive reference is available through Swagger UI in development.

## Artisan Commands

```bash
php artisan accounts:rebuild-totals      # Rebuild monthly and yearly account totals
php artisan accounts:reconcile-balances  # Check and fix stored account balances
php artisan make:manage                  # Scaffold a full CRUD "manage" feature
php artisan remove:manage                # Remove a scaffolded manage feature
```

## Testing & Code Quality

```bash
composer test            # Pest (Feature & Unit)
npm run test:e2e         # Playwright
composer analyse         # Larastan
vendor/bin/pint          # Code formatting
npm run check:api-routes # API route consistency check
```

## AI Context Structure

The repo is set up so AI coding agents (Claude Code) follow project conventions and get checked automatically.

| Path | Purpose |
| --- | --- |
| `CLAUDE.md` | Entry point loaded by Claude Code. Bootstraps Laravel Boost, which generates `AGENTS.md`. |
| `AGENTS.md` | Generated guidelines (Laravel, PHP, Pest, Pint, Boost tools) tailored to this app. Tells the agent to read `.ai/rules` before editing. |
| `boost.json` | Laravel Boost config: target agent, guidelines/MCP on, and installed skills. |
| `.mcp.json` | Registers the `laravel-boost` MCP server (`php artisan boost:mcp`): docs search, DB schema/query, logs, app info. |
| `.ai/rules/` | Committed, team-shared project rules grouped by area (architecture, views, tables, testing, API docs, etc.). `index.md` maps file globs to rule files; the agent reads every matching rule before touching a path. |
| `.claude/skills/` | Skills installed by Boost (Laravel best practices, testing, Tailwind, deployment). |
| `.claude/settings.json` | Hook wiring. |
| `.claude/hooks/` | PHP scripts that enforce the rules automatically. |
| `DESIGN.md` | Design system tokens (colors, typography) used by the UI and checked by a hook. |
| `graphify-out/` | Generated knowledge graph of the codebase so the agent can query architecture instead of scanning files. |

Key conventions enforced by the rules and hooks:

- All features use the REST API. Page controllers only return views; API controllers are one per entity and stay thin.
- Controller → Service → Repository (with interface and binding).
- Every API endpoint has OpenAPI annotations.
- Views follow `DESIGN.md` and Atomic Design, with EN and ID text, skeleton loading, and no inline CSS or JS.
- Forms validate on the client first and show errors at each input.
- Every feature needs a Pest test and a Playwright spec.
- CRUD "manage" features are scaffolded with `php artisan make:manage`, not written by hand.

Hooks run on every edit (`validate-*`, `remind-*`), and stop hooks run graphify, Larastan, Pest, and Playwright when the agent finishes.

To add a rule, ask the agent to record it (Boost `record-rule`) with a glob, title, and short note. It is saved under `.ai/rules` and shared through git.

## License

Released under the [MIT license](LICENSE).
