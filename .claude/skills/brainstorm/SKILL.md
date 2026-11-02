---
name: brainstorm
description: "Toggleable UI-only mode for this project. `/brainstorm on|off|status` flips it. While ON, build only the view layer (Blade, CSS, front-end JS with static/mock data) and never touch backend or database; the deferred backend/DB work is written to docs/brainstorm/backlog.md. On any 'implement' request while ON, ask the user first whether to implement the integration. Also check .claude/skills/brainstorm/state before starting any page, view, component or feature work."
---

# Brainstorm mode

State lives in `.claude/skills/brainstorm/state` (single word: `on` or `off`, default `off`). Read it before starting any feature, page, view or component work; if it says `on`, follow this skill even when `/brainstorm` was not typed in this session.

## Toggle
- `/brainstorm on` -> write `on` to the state file, confirm in one line.
- `/brainstorm off` -> write `off`, confirm. If the backlog has pending items, mention the count and offer to implement them (see Implement).
- `/brainstorm status` (or no argument) -> read the state file and report it plus the number of pending backlog items.

## When ON: view only
Build just what the user can see and click:
- Blade views, components, skeletons, CSS, and front-end JS driven by static/mock data (inline fixtures in the page JS are fine). Still follow `.ai/rules/index.md` for views/JS (design system, i18n EN+ID, responsive, no inline JS/CSS, skeleton loading, vite input).
- Do NOT create or edit: migrations, models, factories, seeders, repositories, services, form requests, API resources, API controllers, API routes, `php artisan make:manage`, or Pest/Playwright tests for backend behaviour. Do not run `migrate`.
- A page route plus a page-only controller returning the view is allowed (the page must be reachable). Nothing else in `app/`.
- Do not wire `resources/js/shared/http.js` calls to real endpoints; use mock data and mark the spot with a `// brainstorm: mock` comment.

## Park backend work in markdown
Every backend/DB need discovered while building the view goes into `docs/brainstorm/backlog.md` (create from the template below if missing), one section per feature. Append; never overwrite other features' sections.

```markdown
# Brainstorm backlog

## <Feature name> (<YYYY-MM-DD>)
Status: pending | implemented
Views: <paths of the Blade/JS files built>
### Database
- table `x`: columns, types, nullable, indexes, FKs, relations (user_id per multi-user rule)
### API (REST, versioned)
- METHOD /api/v1/... : purpose, request fields + validation, response shape
### Backend
- model / repository / service / form request / resource notes
- permissions, edge cases, business rules
### Replace mocks
- file:line of each `brainstorm: mock` to swap for real http.js calls
### Tests
- Pest + Playwright cases to add
```

End each turn that built UI with a one-line note: what was built, and that backend items were added to the backlog.

## Implement requests
If the user says "implement", "integrate", "wire up", or similar (while ON, or when a backlog item exists for it):
1. Do NOT start. Ask via AskUserQuestion: "Implement the backend/DB integration for <feature> now?" Options: implement now (turn brainstorm off first? recommended: keep state as is unless the user says otherwise) / keep UI-only and just update the backlog / cancel.
2. Only on an explicit yes: read the feature's backlog section, implement it fully per `.ai/rules` (use `make:manage` where applicable, REST API, repository pattern, tests), replace the mocks, set the section `Status: implemented`. Temporarily lifting the view-only restriction for that feature is allowed; do not change the state file unless asked.
