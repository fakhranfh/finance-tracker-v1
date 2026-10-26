---
paths:
  - '**'
---

# Dev-only Autofill & Generate Data

## Autofill — client-side JS, no REST endpoint
Every feature with a form must provide autofill (fill the form with plausible values), available **only in local/dev environment** (the "Autofill" button itself must only render when `app()->isLocal()`, e.g. guard it in the Blade template).

Autofill values are generated entirely in JS — do not implement autofill as a REST/API endpoint or a PHP trait. Put each feature's fixture in its own dedicated JS file under a `dev-fixtures/` folder (e.g. `resources/js/auth/dev-fixtures/register.js` exporting `registerFixture()`) — never bundle multiple features' fixtures into one file. Shared generators (e.g. random name/email helpers) go in their own file too (e.g. `dev-fixtures/random.js`). Wire the "Autofill" button to call that function directly and fill the form's fields — no `fetch`/network round-trip involved.

On dashboard resource pages (e.g. users create/edit), place the "Autofill" button in the page header, to the right of the title, outside the `<form>`; the dev-tools helper must then locate it with `document.querySelector('[data-dev-autofill]')`, not `form.querySelector`. A resource's create and edit share one form page, so they share one fixture file (e.g. `dev-fixtures/form.js`), per `.ai/rules/views.md`.

## Generate-data — backend, list/table pages only
Generate-data (create/seed sample records) is scoped differently: only add it to pages that display a table/list of data (index/listing pages), where it's used to populate the list with sample rows. Do not add generate-data to a plain form page (e.g. login, register, forgot/reset password) just because the form happens to submit data that ends up in a table elsewhere — it belongs on the page that renders the list itself.

Because generate-data creates real backing records, it stays server-side:
- Generate-data logic lives in its own dedicated trait, separate from any other concern, e.g. `app/Traits/{Feature}/GeneratesSampleData.php`.
- Controllers/services consume this trait rather than duplicating sample-data logic per feature.
- Each feature's own controller consumes its own trait directly. Do not centralize generate-data endpoints for multiple features into a single shared/dev controller.
- Guard the endpoint/action with `app()->environment('local')` (or equivalent), never expose in production.
- Tests for a feature's generate-data endpoint go in that feature's own test file, not bundled together into one shared test file covering multiple features.

## Sample values must be realistic, never lorem ipsum
Every generator of sample data (model factories, `GeneratesSampleData` traits, JS autofill fixtures in `dev-fixtures/`, and the seed data of Pest/Playwright tests) must produce values that look like real data for this app (an Indonesian personal finance tracker), not placeholder text.

- Do not use `fake()->words()`, `sentence()`, `paragraph()`, `text()`, `lorem*`, or JS word lists like `alpha bravo cedar` for user-facing fields. Pick from a short curated list of real-looking values instead (`fake()->randomElement([...])` in PHP, a `randomItem([...])` list in JS): Indonesian person names (`Budi Santoso`, `Siti Rahmawati`), bank/e-wallet names (`BCA`, `GoPay`), and notes that describe a real situation (`Talangan bayar kontrakan`).
- Amounts are rupiah-sized (multiples of 1.000 or 10.000, e.g. 50.000 to 5.000.000), not `randomFloat(2, 1, 1000)`.
- Dates are near today (a due date a few weeks ahead, a past record date), not random decades.
- Related fields stay consistent: a paid amount never exceeds the total, `is_paid` matches `paid_amount`, a note fits the record's type (debt vs receivable).
- The JS fixture and the PHP factory for one feature use the same style of values.
- `make:manage` stubs only emit generic placeholders. After generating, replace them in the factory, the JS fixture and the sample-data trait with realistic values for that entity.
