---
paths:
  - 'routes/web.php'
  - 'routes/api.php'
---

# Route Grouping

## Use `Route::prefix()` for routes sharing a path
When two or more routes share the same leading path segment, wrap them in `Route::prefix('segment')->group(...)` instead of repeating the segment in every route. Inside the group, paths are relative (`'/'`, `'/create'`, `'/{id}/edit'`).

- Nest prefixes for deeper shared paths (`Route::prefix('goals')` -> `Route::prefix('{goalId}/sub-goals')`), and put shared constraints on the prefix (`->whereNumber('goalId')`).
- Keep full route names (`->name('goals.sub-goals.index')`); do not switch to `->name()` group prefixes unless asked.
- `Route::resource(...)` stays outside the prefix group (it already builds its own path); put the custom routes of the same entity in a `Route::prefix(...)` group next to it.
- `defaults()` is not a group attribute, so keep `->defaults('type', $type)` on each route.
- Declare static/specific routes (`/generate`, `/create`, `/order`) before wildcard routes (`/{id}`) so they are not shadowed.
- After refactoring, verify nothing changed: compare `php artisan route:list --json` before and after (method, uri, name, action must be identical).

## Generator
`make:manage` already emits `Route::prefix('{slug}')->group(...)` blocks in `routes/web.php` and `routes/api.php`, and `remove:manage` strips them. Keep both commands in sync with this rule if the grouping shape changes.
