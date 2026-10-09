---
paths:
  - '**'
---

# General

## All features must access backend via REST API
Every feature must access its backend functionality through a REST API, not via direct Blade-server-rendered controller logic tied to the frontend. Build dedicated API routes/controllers (Eloquent API Resources, versioned) for backend access, and have the frontend consume them over REST.

## app/Http/Controllers is for pages only; features live under app/Http/Controllers/Api
Every controller under `app/Http/Controllers/**` that is NOT under `app/Http/Controllers/Api/**` must only render a page: return a `View`/`Illuminate\View\View` (or redirect to another page), with no Form Request validation, no Service/Repository business-logic calls, and no direct Eloquent/`DB::` calls. Any create/update/delete or data-fetching feature belongs in an API controller under `app/Http/Controllers/Api/**`, consumed by the page's own JS file via `resources/js/shared/http.js` (see `.ai/rules/http-client.md`). Route-parameter usage that only shapes the URL (e.g. passing an `{id}` straight into the view for the frontend JS to fetch) is fine; calling `User::find($id)` or a Service method to prefetch data for the view is not — the page's JS fetches it from the API instead.

Reference implementation: `App\Http\Controllers\Auth\AuthPageController` (pages only) + `App\Http\Controllers\Api\Auth\*` (the actual auth logic), and `App\Http\Controllers\UserPageController` + `App\Http\Controllers\Api\UserController`.

This is enforced by a Claude Code hook (`.claude/hooks/validate-page-only-controllers.php`, registered in `.claude/settings.json` as a `PostToolUse` hook on `Edit|Write` of `app/Http/Controllers/**`), which blocks the edit if a non-`Api` controller injects a Service, type-hints a `FormRequest`, or calls a model/`DB::` method directly.

## One controller file per entity in app/Http/Controllers/Api, not one file per action
API controllers must group all of an entity's REST actions (`index`, `show`, `store`, `update`, `destroy`) into a single controller class, e.g. `App\Http\Controllers\Api\UserController`. Do not create a separate single-action/invokable controller file per endpoint for a resourceful entity (no `Api\User\IndexUserController`, `Api\User\StoreUserController`, etc.). Route it with `Route::resource(...)` (or explicit per-method routes pointing at the same class) rather than one `Route::get(...)`/`post(...)` per invokable class.

Note: `App\Http\Controllers\Api\Auth\*` predates this rule and stays as-is (one class per auth action, since login/register/logout/etc. aren't CRUD on one entity) — follow the entity-controller shape for new resourceful entities going forward.

## JS placement: shared is for cross-page utilities only; component modules get their own folder
- `resources/js/shared/` holds only utilities used across pages (e.g. `http.js`, `modal.js`, `loading-state.js`).
- A module set that serves one component is grouped in a folder named after that component, e.g. `resources/js/shared/table/` (`table.js` plus its `table-*.js` helpers).
- Modules inside that folder import siblings with `./` and shared utilities with `../`.
- Page folders (`resources/js/<feature>/`) keep their own page scripts and import the component from its folder, e.g. `../shared/table/table.js`.

This is enforced by a Claude Code hook (`.claude/hooks/validate-single-controller-per-entity.php`, registered in `.claude/settings.json` as a `PostToolUse` hook on `Write` of `app/Http/Controllers/Api/**/*.php`), which blocks creating a new invokable (`__invoke`-only) controller inside a subdirectory of `app/Http/Controllers/Api/` (e.g. `app/Http/Controllers/Api/Product/CreateProductController.php`).

## Register every new page JS entry in vite.config.js
Whenever you add a new JS file that a view loads directly with `@vite('resources/js/...')` (page entry points, not modules only imported by other JS), add it to the `input` array in `vite.config.js` in the same change. Otherwise `npm run build` omits it from the manifest and the page returns a 500 (`Unable to locate file in Vite manifest`) in CI/production, even though it can pass locally with a stale `public/build` or a `hot` file.
