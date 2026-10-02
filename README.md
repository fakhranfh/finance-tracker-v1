# Laravel 13 Boilerplate

A **Laravel 13** web application boilerplate with complete authentication, user management, and a Blade + Tailwind CSS interface. Server-rendered pages call a session-based REST API via `fetch`, so all backend logic lives in the API layer rather than in page controllers.

## Features

- **Authentication**: registration, login, logout, forgot password, and reset password.
- **Profile**: view and update the user profile.
- **User management**: list, create, edit, delete, and bulk delete.
- **Multi-language**: English (`en`) and Indonesian (`id`), switchable via `/lang/{locale}`.
- **API documentation** with Swagger (L5-Swagger).
- **Local-only helpers**: dev auto-login and user generator (only enabled when `APP_ENV=local`).
- **Code quality**: Pest, Playwright (E2E), Larastan, and Laravel Pint.

## Tech Stack

| Area | Technology |
| --- | --- |
| Backend | PHP ^8.4, Laravel ^13.17, Laravel Sanctum |
| Frontend | Blade, Tailwind CSS 4, Vite 8 |
| Database | PostgreSQL (default in `.env.example`) |
| API Docs | darkaonline/l5-swagger |
| Testing | Pest 5, Playwright |
| Static analysis | Larastan, Pint |

## Project Structure

```
app/
├── Console/Commands   # Artisan commands (MakeManage, RemoveManage, SeedE2eFixture, Version)
├── Http/              # Controllers (pages & API), Middleware, Requests, Resources
├── Models/            # User and concerns
├── Repositories/User  # UserRepository + interface
├── Services/          # AuthService, UserManagementService
└── Traits/            # Per-feature logic (Login, Register, Profile, etc.)
routes/
├── web.php            # Blade pages
└── api.php            # Session-based API endpoints (web middleware + auth:sanctum)
lang/                  # en & id translations
tests/                 # Feature (Pest), Unit, and Playwright
```

## Installation

Prerequisites: PHP 8.4+, Composer, Node.js, and PostgreSQL.

```bash
git clone <repo-url> laravel-13-boilerplate-v1
cd laravel-13-boilerplate-v1
composer setup
```

`composer setup` installs dependencies, copies `.env`, generates the app key, runs migrations, and builds assets. Adjust the database credentials in `.env` (`DB_*`) **before** migrating if needed.

## Running

```bash
composer dev
```

This starts the server (`php artisan serve`), the queue listener, and Vite together. The app is available at `http://localhost:8000`.

## Routes

**Pages**: `/`, `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/dashboard`, `/profile`, `/users`, `/users/create`, `/users/{id}/edit`.

**API** (prefix `/api`):

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/register`, `/login`, `/forgot-password`, `/reset-password` | Authentication (public) |
| POST | `/logout` | Sign out (auth) |
| GET/PUT | `/profile` | Profile (auth) |
| GET/POST/PUT/DELETE | `/users` | User CRUD (auth) |
| DELETE | `/users` | Bulk delete (auth) |
| POST | `/dev/auto-login`, `/users/generate` | `local` environment only |

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
| `.ai/rules/` | Committed, team-shared project rules grouped by area (API docs, auth, models, migrations, views, tables, testing, repository pattern, etc.). `index.md` maps file globs to rule files; the agent reads every matching rule before touching a path. |
| `.claude/skills/` | Skills installed by Boost: `laravel-best-practices`, `testing-best-practices`, `tailwindcss-development`, `deploying-to-cloud`, `infer-conventions`. |
| `.claude/settings.json` | Hook wiring (see below). |
| `.claude/hooks/` | PHP scripts that enforce the rules automatically. |
| `DESIGN.md` | Design system tokens (colors, typography) used by the UI and checked by a hook. |
| `graphify-out/` | Generated knowledge graph of the codebase (`GRAPH_REPORT.md`, `graph.json`, `graph.html`) so the agent can query architecture instead of scanning files. |

### Rules in `.ai/rules/`

`index.md` maps file globs to rule files. The agent must read every matching rule before editing a path. Some rules apply to all files (`**`), such as `general`, `testing`, `e2e-testing`, `dev-autofill`, `manage-generator`, and `graphify`.

| Area | File | Summary |
| --- | --- | --- |
| Architecture | `general.md` | All features use the REST API. `app/Http/Controllers` is for pages only; features live in `Controllers/Api`, one controller per entity. |
| | `repository-pattern.md` | Controller → Service → Repository (+ interface and binding), with examples, best practices, and testing. |
| | `manage-generator.md` | Manage features must be scaffolded with `php artisan make:manage`. Covers post-generate steps, keeping the stubs in sync with Manage Users, and removal with `php artisan remove:manage` (the agent must get an explicit yes from the user first). |
| | `api-docs.md` | Every controller and method needs OpenAPI/L5-Swagger annotations, kept outside the controller body. Swagger UI is dev-only. |
| Frontend | `views.md` | Follow `DESIGN.md` and Atomic Design. Datetimes shown in the user's timezone, skeleton loading, EN+ID pages, no computed PHP in Blade, JS and CSS in separate files, loading state on buttons, reusable table and modal components, shared create/edit page, red destructive buttons. |
| | `tables.md`, `js.md` | Tables render as cards on mobile. Data tables use pointer pagination, search and sort, and client-side bulk selection. |
| | `form-validation-errors.md` | Validate client-side first. Show errors at each input and scroll to it. The snackbar is only for more than 3 errors. Success alerts are green. |
| | `http-client.md` | Use the shared HTTP client and never hardcode page paths. |
| | `dashboard-navigation.md` | Sidebar and topbar items live in their own config files. |
| | `dev-autofill.md` | Autofill is client-side and dev-only. Generate-data is backend-only, for list pages. |
| Data & auth | `models.md` | Use the `HasUserTimezone` trait for datetime conversion. |
| | `migrations.md` | The users table has a timezone column. Enum columns need a matching PHP enum. |
| | `auth.md` | Detect timezone from the IP address on login. |
| | `password-validation.md` | Always require strong passwords. |
| Testing | `testing.md` | TDD, test DB matches the production connection, run Larastan and tests after changes. A feature needs a Pest test and a Playwright spec. |
| | `e2e-testing.md` | Every feature and page gets a Playwright spec. Run only the affected specs. |
| | `responsive-testing.md` | Test every breakpoint in the browser, mobile first. |
| Tooling | `graphify.md` | Query the knowledge graph in `graphify-out/` before browsing code, and run `graphify update .` after every file change. |

### Hooks

- **`validate-*`** (after Write/Edit): block violations of conventions, e.g. repository pattern, page-only controllers, single controller per entity, Blade without inline CSS/JS, design system usage, multilingual views, timezone handling, migration enums, and API/page route checks.
- **`remind-*`**: nudge the agent about missing tests, Swagger docs, form validation errors, and dev autofill.
- **Stop hooks** (when the agent finishes): run graphify, Larastan, Pest, and Playwright, skipping a step when nothing relevant changed since its last run or no test matches the changed files (Playwright is also skipped when no dev server is running).

### Example: "build product management"

1. **Context**: the agent reads `AGENTS.md` and `.ai/rules/index.md`, then every rule matching the paths it will touch. `manage-generator.md` says manage features must be scaffolded with `php artisan make:manage`, not hand-written.
2. **Generate**: the agent runs, for example:
   ```bash
   php artisan make:manage Product --fields="name:string,description:text?,price:decimal,stock:integer,is_active:boolean" --label-id=Produk --labels-id="name:Nama,price:Harga,stock:Stok"
   ```
   Mention the fields in your prompt; otherwise the agent has to guess or ask.
3. **Output**: model, migration, factory, repository (+ interface and binding), service, form requests, API resource, API and page controllers, routes (`web.php`, `api.php`, `resources/js/routes.js`), Blade views and skeletons, JS, EN/ID lang files, sidebar/dashboard entry, Vite input, a Pest test, and a Playwright spec.
4. **Follow-up**: the agent runs `php artisan migrate`, `vendor/bin/pint --dirty`, `npm run build`, then the Pest and Playwright tests.
5. **Checks**: `validate-*` hooks run on every edit, and the stop hooks run graphify, Larastan, Pest, and Playwright when the agent finishes.

Result: Product follows the same structure as Users. Needs outside the manage pattern (relations, image uploads) are added by hand after generating, and the hooks still check them.

### Example: "build a landing page"

Unlike product management, there is no generator for this. The agent builds it by hand, guided by the rules and hooks that match the paths it touches (`resources/views/**`, `resources/js/**`, `resources/css/**`, `routes/web.php`). The repo already has a `landing` view with `hero`, `features`, and `cta` organisms, so the agent should extend those rather than start over.

1. **Context**: the agent reads `AGENTS.md` and `.ai/rules/index.md`, then the matching rules: `views.md`, `responsive-testing.md`, `general.md`, `testing.md`, and `e2e-testing.md`. It also reads `DESIGN.md` for colors and typography.
2. **Build**: following `views.md`, the page uses:
   - Atomic Design (atoms, molecules, organisms, templates) and reusable components for repeated sections
   - English and Indonesian text through `lang/` files
   - no computed PHP in Blade, and JS and CSS in separate files
   - design tokens from `DESIGN.md`, not ad-hoc colors
   - buttons with a loading state, and skeleton loading where content loads asynchronously
3. **Responsive check**: the agent opens the page in a new browser tab and checks 375, 768, 1024, and 1440 px (mobile first). It looks for horizontal scroll, overlap, and clipping, and reports which viewports it checked.
4. **Tests**: it adds a Playwright spec for the new page and runs only the affected specs. Any backend logic needs a Pest test too.
5. **Checks**: the `validate-*` hooks run on every edit (no inline CSS or JS, design system, multilingual view, Blade pattern). The stop hooks run graphify, Larastan, Pest, and Playwright at the end.

Result: the landing page matches the rest of the app's look and structure, works in both languages and at every breakpoint, and ships with a test. The cost is more steps than a plain "write an HTML page", because each rule adds work.

### Example: "add a create product feature"

The result depends on whether Product already exists.

- **Product does not exist**: the agent uses `php artisan make:manage Product ...` as in the first example. The generated feature already includes list, create, edit, and delete, so no extra work is needed.
- **Product exists** (for example it was generated earlier): the agent adds or changes the create flow by hand, guided by the rules for each layer it touches.

1. **Context**: the agent reads `.ai/rules/index.md`, then the rules matching the paths in scope: `general.md`, `repository-pattern.md`, `api-docs.md`, `views.md`, `form-validation-errors.md`, `http-client.md`, `dev-autofill.md`, `testing.md`, and `e2e-testing.md`.
2. **Test first**: `testing.md` asks for TDD, so the agent writes the Pest test before the implementation.
3. **Backend**:
   - the form request validates the input
   - the service holds the logic and the repository (via its interface) does the query
   - the API controller in `Controllers/Api` stays thin, one controller per entity
   - the page controller in `app/Http/Controllers` only returns the view
   - the new endpoint gets OpenAPI annotations, outside the controller body
4. **Frontend**:
   - create and edit share one page
   - the JS calls the API through the shared HTTP client and `resources/js/routes.js`, with no hardcoded paths
   - the form validates on the client first and shows each error at its own input
   - the submit button shows a loading state
   - dev-only autofill is client-side
   - all text goes through the EN and ID lang files
5. **Tests and checks**: the agent adds or updates the Playwright spec and runs only the affected Pest and Playwright tests. The `validate-*` hooks run on every edit, and the stop hooks run graphify, Larastan, Pest, and Playwright at the end.

Result: the new feature follows the same layers as Users. A controller that holds logic, an undocumented endpoint, or a Blade file with inline JS is flagged by the hooks before the work is finished.

### Adding a rule

Ask the agent to record it (Boost `record-rule`) with a glob, title, and short note. It is saved under `.ai/rules` and shared through git.

## License

Released under the [MIT license](LICENSE).
