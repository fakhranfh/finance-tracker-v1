---
paths:
  - '**'
---

# E2E Testing (Playwright)

## Every feature gets an E2E test
Every time you add or change a feature, create a corresponding end-to-end test using Playwright.

## Every new page gets a Playwright spec
Any time a new page/route is added (a new Blade view backing a `routes/web.php` entry, e.g. `dashboard.blade.php` + `GET /dashboard`), create a matching `tests/playwright/<page>.spec.ts` in the same change — covering at minimum the happy path of reaching the page and its key visible elements, plus the auth-guard redirect if the route is protected. Never ship a new page without one.

## Always run Playwright after changing a Blade or JS file
Any time a `resources/views/**/*.blade.php` or `resources/js/**/*.js` file is added, edited, or removed, update the relevant Playwright spec(s) if the change affects markup/selectors/behavior they cover, then run the Playwright suite (e.g. `npx playwright test`) before considering the change done. Never leave a Blade/JS change unverified by the e2e suite when a covering spec exists.

## Location
Store all Playwright tests inside `tests/playwright/`. Do not scatter e2e specs in other test directories (`tests/Feature`, `tests/Unit` remain for Pest/PHPUnit only).

## Always run Playwright after ANY file change
Beyond Blade/JS: any change to app code (`app/**`, `routes/**`, `config/**`, `lang/**`, `database/**`, `AppServiceProvider` validation defaults such as `Password::defaults()`) or to a spec itself must be followed by running the Playwright suite (`npx playwright test`) before the change is considered done. Backend changes can break e2e flows without touching any view (e.g. tightened password rules made the users create spec fail with `password123`). If a spec fails, fix the spec or the code, and re-run until green. Only skip for changes that cannot affect runtime behavior (docs, comments, `.ai/rules/**`).

## Run only the affected specs
Run only the Playwright spec(s) for the feature you added or changed (`npx playwright test tests/playwright/<page>.spec.ts`), not the whole suite. Run the full suite only when the user explicitly asks for it. This overrides the "run the Playwright suite" wording in the sections above.
