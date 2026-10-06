---
paths:
  - 'resources/views/components/organisms/table.blade.php,resources/views/**/*index.blade.php,resources/js/**/*index.js'
---

# Js

## Data tables: pointer pagination, skeleton loading, search+sort, bulk delete (client-side selection only)
Every data table built on `<x-organisms.table>` must include:
- Pagination buttons use `cursor-pointer` (see `renderPagination` in `resources/js/shared/table/table-pagination.js`).
- Show a skeleton (`resources/views/components/organisms/*-table-skeleton.blade.php` pattern, atoms/skeleton.blade.php) while the initial data loads, and re-trigger it while a page/sort/search request is in flight.
- Always provide a search filter (`<x-molecules.table-toolbar>` `data-table-search` input) and clickable sort-by-column headers (`headers` prop items as `['label' => ..., 'sort' => 'column']`, rendered with `data-sort-key`/`data-sort-icon` in `table.blade.php`).
- Always provide bulk delete: a header checkbox (`data-table-select-all`) selects only the currently visible/paginated rows. When every visible row is selected and more rows exist, the bulk bar offers a Gmail-style "Select all N" button (`data-table-select-all-matching`) plus "Clear selection". Do NOT add a server endpoint to fetch all IDs — in that mode the bulk-delete request sends `{ all: true, search }` and the server deletes by filter. Row selection itself stays client-side (`resources/js/shared/table/table-selection.js`).
- Persist the selected IDs in `localStorage` via `TableSelection`, and clear that storage on `beforeunload`/`pagehide` so selection never leaks across visits.
- The bulk-action bar (selected count + delete button) renders as a `<tr>` inside the table (`data-table-bulk-row`), directly under the header, not in the toolbar next to search.

## Reusable components: one entry point, one config object
When building a reusable UI component (table, etc.), expose it through a single function that takes a single config object, e.g. `createTable({ ids, routes, row, ... })` in `resources/js/shared/table/table.js`.
- Page scripts (`resources/js/<module>/index.js`) only import and call that one function — no wiring of sub-modules (selection, search, sort, skeleton, delete, etc.) in the page script. That wiring lives inside the component.
- Likewise, Blade components take their options as a single `:config` array (plus data shared with sibling components, like `:headers`) instead of many separate props or `data-*` attributes; the component renders the `data-*` attributes itself (see `<x-organisms.table :config="[...]">`).
- Keep `stubs/manage/*` in sync so generated modules use the same single-call pattern.
