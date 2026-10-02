---
paths:
  - 'resources/views/components/organisms/table.blade.php'
  - 'resources/views/**/index.blade.php'
  - 'resources/js/**/index.js'
---

# Tables render as cards on mobile

Every data table (`x-organisms.table`) must render as stacked cards below the `sm` breakpoint (640px); never leave a horizontally scrolling table on phones. Cells that show a field need a `data-label` (from the header text) so the card can show the label; the checkbox cell and the actions cell have no label. On mobile the actions sit at the top-right of the card as icon-only buttons (text kept `sr-only` + `title`), and desktop keeps text links. JS-rendered rows (e.g. `renderRow` in `resources/js/users/index.js`) must follow the same pattern.

If a table has more than 3 field columns, the card shows only the first 2 fields; the third row is a "Show more" toggle (`applyCardCollapse` in `resources/js/shared/table-card.js`) that reveals the rest.

On mobile the toolbar's search/per-page and the header sort buttons are replaced by a sticky footer (`x-organisms.table-mobile-bar`) with Filter and Sort buttons that open modals; the modal controls drive the same state as the desktop controls.
