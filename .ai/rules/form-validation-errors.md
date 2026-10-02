---
paths:
  - 'resources/js/**'
  - 'resources/views/**'
---

# Form Validation Error Display

## Never put these helpers in a single catch-all `shared.js`
There must never be a `resources/js/shared.js` (or `resources/js/auth/shared.js`) file. Each helper lives in its own file under the top-level `resources/js/shared/` folder (not nested under `auth/`), one concern per file:
- `shared/csrf.js` — `csrfToken()`
- `shared/api-token.js` — `storeApiToken`, `clearApiToken`, `getApiToken`
- `shared/loading-state.js` — `showSkeleton`, `hideSkeleton`, `setButtonLoading`
- `shared/field-errors.js` — `clearFieldErrors`, `validateForm`, `showFieldErrors`, `showGenericError`
- `shared/error-snackbar.js` — `scrollToField`, `showErrorSnackbar`, `hideSnackbar`
- `shared/form-success.js` — `showSuccess`
- `shared/http.js` — the fetch client (`get`, `post`, `put`, `patch`, `del`; see `.ai/rules/http-client.md`)

Import each helper from its own file (e.g. `import { showFieldErrors } from '../shared/field-errors.js'`), never re-add a single `shared.js` re-exporting everything. This is enforced by `.claude/hooks/remind-form-validation-errors.php`, which flags any new `resources/js/auth/shared.js` or `resources/js/shared.js` file.

## Validate client-side first, before ever calling the API
Every submit handler must run `validateForm(form)` (from `resources/js/shared/field-errors.js`) right after `clearFieldErrors`/`hideSnackbar` and before building the request payload or calling `post`. `validateForm` runs the browser's native constraint validation (`required`, `type=email`, `minlength`, `pattern`, etc.) against every named field and returns a Laravel-shaped `{ field: [message] }` map. If it returns any entries, call `showFieldErrors(form, clientErrors)` and `return` immediately — do not hit the API when client-side validation already caught the problem. Server-side 422 handling (`showFieldErrors(form, data.errors)`) stays in place for rules the browser can't check (uniqueness, password confirmation match, current-password checks, etc.).

## Errors are always rendered client-side via JS, never a static alert box
Do not add a generic `data-form-alert` (or any other static alert/banner) element to a form for showing errors — this pattern was removed from the auth forms. Every validation and submission error, from the very first failed submission, must be rendered by JS via `showFieldErrors`/`[data-error-for]` (field errors) or `showGenericError` (single non-field errors, rendered as a field error too — see below). Never render errors via server-side Blade (`@error`, session-flashed alerts) on these fetch-based forms.

## Show each error only at its own input, then scroll to it
When a form submission returns validation errors, display each field's error message only next to that field's input (via `[data-error-for="{field}"]`), never as a single combined block. After rendering the errors, scroll the page to the first errored input (e.g. `element.scrollIntoView({ behavior: 'smooth', block: 'center' })`) so the user immediately sees what needs fixing.

## The error snackbar (top-right) is only for more than 3 errors
The `[data-error-snackbar]` element in the layout (fixed, top-right on desktop) is a summary list — it must stay hidden unless there are **more than 3** errors at once. A single non-field error (invalid credentials, a network failure) must never open the snackbar; render it as a field error instead (see `showGenericError` below).

## Implementation
Use `showFieldErrors(form, errors)` in `resources/js/shared/field-errors.js` for this behavior — do not hand-roll error rendering, scrolling, or a snackbar per page. It must:
- Clear previous errors first (`clearFieldErrors`), same as today.
- Render each error at its `[data-error-for="{field}"]` element.
- Scroll/focus the first errored input.
- Only when `Object.keys(errors).length > 3`, also render a clickable error-summary snackbar (`[data-error-snackbar]` in the layout) whose items scroll to their input on click.

Use `showGenericError(form, message, field = 'email')` for any error that isn't a field error (e.g. a 401/422 "invalid credentials" response, or a caught network exception). It renders the message via `showFieldErrors` against the given field, so it never opens the snackbar on its own. Call `hideSnackbar()` (and `clearFieldErrors`) at the start of every submit attempt, before the request goes out.

Every new form (register, login, reset-password, or any future feature form) must call this shared helper on its validation-error path instead of writing its own error-rendering logic.

This is enforced by a Claude Code hook (`.claude/hooks/remind-form-validation-errors.php`, registered in `.claude/settings.json` as a `PostToolUse` hook on `Edit|Write` of `resources/js/**` and `resources/views/**`), which flags JS files that render form errors (e.g. reference `data-error-for` or a validation `errors` object) without calling `showFieldErrors` from `resources/js/shared/field-errors.js`, flags any reintroduced `data-form-alert`, and flags any new catch-all `shared.js` file.

## Alerts: success is always green, no alert of any kind while a skeleton is showing
Success alerts/flash messages always use the green success tokens (border-success/20 bg-success/10 text-success).
Never show any alert (success, error, warning, info, flash, snackbar) while a skeleton loading state is still visible: show it only after the data load finishes and the skeleton is hidden (e.g. in loadX().then(...)), not before the fetch starts.

## Don't reload the page after a submit that stays on the same page
After a successful form submit, if the user remains on the same page, do not refresh/reload it (no window.location.reload()/href to itself). Update the UI in place: refill fields from the response, clear password inputs, show the success alert, and sync any other on-page copies of the data (e.g. the topbar name). Redirecting to a different page (e.g. users form -> /users) is fine.
