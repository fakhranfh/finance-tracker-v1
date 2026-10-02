---
paths:
  - 'app/Http/Controllers/Api/**'
  - 'routes/api.php'
---

# REST API Documentation (Swagger UI)

## Use L5-Swagger / OpenAPI annotations
Document REST API endpoints using Laravel Swagger UI (`l5-swagger` / `darkaonline/l5-swagger` OpenAPI annotations), served at the app's Swagger UI route.

## Keep docs in sync with code
Every time you add or change a REST API feature (new endpoint, changed request/response shape, changed validation rules, changed route), update the corresponding Swagger/OpenAPI annotations in the same change, then regenerate the docs (`php artisan l5-swagger:generate`).

## No undocumented endpoints
Do not merge a new or changed API endpoint without matching Swagger annotations. Treat missing/stale Swagger docs as incomplete work.

## Swagger UI is dev-only
The Swagger UI route (and doc generation exposed to the browser) must only be reachable in local/dev environments (guard via `L5_SWAGGER_GENERATE_ALWAYS`/config, `app()->environment('local')`, or equivalent route/middleware gating). Never expose Swagger UI in production.

## Keep OpenAPI annotations out of the controller body
Do not write `#[OA\...]` attribute blocks directly on controller action methods. Put each feature's OpenAPI annotations in their own dedicated trait (e.g. `app/Traits/{Feature}/DocumentsApi.php`), one trait per feature, consumed by that feature's own controller — mirroring the per-feature trait split used for autofill/generate-data (see `.ai/rules/dev-autofill.md`). Do not centralize multiple features' annotations into one shared docs trait/class.

## Always document every new controller and every new controller method
Whenever you create a new API controller, or add a new public method/action to an existing API controller, you MUST write its Swagger/OpenAPI documentation in the same change. Never leave a controller or method undocumented, even if it looks internal or is local-only (e.g. generate-data endpoints).
- New controller: create its own `app/Traits/{Feature}/DocumentsApi.php` trait and `use` it in the controller.
- New method on an existing controller: add a matching `document{Feature}{Action}Endpoint()` method with `#[OA\...]` to that feature's `DocumentsApi` trait (path, summary, tags, security, parameters, request body, all response codes).
- Register any new tag in `app/Http/Controllers/Controller.php` and reuse shared schemas via `$ref` where possible.
- Regenerate with `php artisan l5-swagger:generate` and confirm the new path appears in `storage/api-docs/api-docs.json`.
