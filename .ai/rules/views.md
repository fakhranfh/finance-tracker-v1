---
paths:
  - 'resources/views/**'
---

# Views

## Display timezone-converted datetime in views
All Blade templates must display timestamp and datetime values that have been converted to the authenticated user's timezone. Never display raw UTC or unconverted datetime; use the HasUserTimezone trait accessors to ensure proper conversion before rendering.

## Follow the design system in DESIGN.md
Every view must follow the design system defined in DESIGN.md at the project root: use its defined colors, typography, spacing/rounded tokens, component conventions, and Do's and Don'ts. Do not introduce ad-hoc colors, fonts, or spacing values outside those defined there. DESIGN.md's contents may change over time as the design system evolves; always defer to whatever it currently specifies.

## Use Atomic Design for views
Always structure UI using Atomic Design principles: atoms (buttons, inputs, labels) → molecules (form groups, search bars) → organisms (navbars, cards, tables) → templates (page layouts) → pages (final views). Organize Blade components/partials accordingly instead of monolithic view files.

## Always use skeleton loading
Every view that renders content must have a corresponding skeleton loading state. Never inline skeleton markup inside the view it belongs to; always define it in a separate Blade component file (e.g. `resources/views/components/atoms/skeleton.blade.php` or a dedicated `*-skeleton.blade.php` component per organism/template) and include it via `<x-.../>`.

## Form skeletons must mirror their inputs
A form skeleton (`*-form-skeleton.blade.php`, shown in edit mode while the record loads) must match the real form field by field, in the same order: each `x-molecules.form-group` (label + input) becomes `<div class="space-y-1.5">` with a label bar (`h-4 w-24`) and an input bar (`h-10 w-full`); each checkbox row becomes `<div class="flex items-center gap-2">` with a `h-4 w-4` box and a label bar (`h-4 w-32`); other control types (select, textarea, radio group) get a bar matching their real height. The submit button bar (`h-11 w-32`) stays last. Never use a fixed count of identical bars. When a field is added, removed or changed in the form, update its skeleton in the same change. `make:manage` generates this from the field types (`formSkeletonRow()`), and `stubs/manage` must stay in sync.

## Every page must be multilingual (English + Indonesian)
Every page must support multiple languages, starting with English and Bahasa Indonesia. Default the locale to English.

## No computed PHP inside Blade files
Never write `@php ... @endphp` blocks (or raw `<?php ?>` tags) inside any `.blade.php` file, including anonymous components. If a view needs computed values (arrays, conditionals, formatted strings, method calls like `request()->routeIs(...)`), move that logic into a class-based Blade component (`app/View/Components/**`, backing the same `resources/views/components/**` view) or a view composer, and expose the result as a public property/method. The Blade template should only reference those variables/methods — it must contain no computation of its own.

## Keep JavaScript in separate files
Always keep JavaScript in separate `.js` files, never inline `<script>` blocks inside Blade templates.

## Keep CSS in separate files
Always keep CSS in separate `.css` files, never inline `<style>` blocks or `style="..."` attributes inside Blade templates.

## Buttons must show a loading state when clicked
Any button that triggers an async action (form submit, fetch call) must switch to a visible loading state while the action is in flight — never leave the button looking idle/clickable during a pending request. Use the `components/atoms/button.blade.php` atom, which renders a hidden `components/atoms/spinner.blade.php` plus a `[data-button-label]` span, and toggle it with the `setButtonLoading(button, isLoading)` helper in `resources/js/shared/loading-state.js` (disables the button, swaps the label for the spinner). Do not hand-roll `disabled`/text toggling per call site.

## Tables and modals must be built as reusable components
Never write a raw `<table>...</table>` or a hand-rolled modal/dialog `<div>` inline inside a page view. Use `resources/views/components/organisms/table.blade.php` (`<x-organisms.table :headers="[...]">...rows...</x-organisms.table>`) for any tabular data, and `resources/views/components/organisms/modal.blade.php` (`<x-organisms.modal id="..." :title="...">...</x-organisms.modal>`) for any dialog/confirmation overlay. Add props/slots to those shared components instead of building a one-off table or modal in a page-specific view. See `resources/views/users/index.blade.php` for the reference usage (table listing + delete-confirmation modal).

This is enforced by a Claude Code hook (`.claude/hooks/validate-table-modal-components.php`, registered in `.claude/settings.json` as a `PostToolUse` hook on `Edit|Write` of `resources/views/**`), which blocks a page/organism-outside-table-or-modal view containing a raw `<table` tag or a `data-modal`-shaped `<div` that isn't inside `components/organisms/table.blade.php` / `components/organisms/modal.blade.php` themselves.

## Extract duplicate components into a template immediately
As soon as the same markup/structure appears in two or more views (e.g. the same card shell, header, or layout wrapper repeated across pages), extract it into a Blade component under `resources/views/components/templates/` right away — do not wait for a third occurrence. Pages should then compose that template instead of repeating the markup.

## Call REST API endpoints through resources/js/routes.js
Never hardcode an `/api/...` path inline inside a page's JS file. Register every REST API endpoint as an entry in `resources/js/routes.js` (grouped by feature, e.g. `routes.auth.login`) and import `routes` from there wherever a `fetch`/`post` (from `resources/js/shared/http.js`) call is made. When adding a new API endpoint, add its path to `routes.js` in the same change.

This is enforced by a Claude Code hook (`.claude/hooks/validate-api-routes.php`, registered in `.claude/settings.json` as a `PostToolUse` hook on `Edit|Write` of `resources/js/**`), which runs `scripts/check-api-routes.js` and blocks the edit if any `resources/js/**/*.js` file other than `routes.js` contains a hardcoded `/api/...` string literal.

## Create and edit share one page
A resource's create and edit forms must be a single Blade view (e.g. `resources/views/users/form.blade.php`) plus a single JS entry (e.g. `resources/js/users/form.js`) — never separate `create.blade.php`/`edit.blade.php` or `create.js`/`edit.js` pairs. Keep the separate routes/URLs (`/users/create`, `/users/{id}/edit`) but point both controller actions at the same view, passing `id` (`null` on create). The view and JS switch mode on that id: edit mode renders the skeleton, loads the record, makes the password optional and submits with `put`; create mode submits with `post`. Autofill fixture is one file per form (`dev-fixtures/form.js`), and the Autofill button lives in the page header (outside the `<form>`, wired via `document.querySelector`).

## Destructive action buttons are red
Buttons for dangerous/destructive actions (delete, bulk delete, and their confirm buttons in modals) must use the button atom's `variant="danger"` (bg-error text-on-error). Inline text-link delete actions use text-error. Never style them as primary/secondary or with ad-hoc `!` overrides.
