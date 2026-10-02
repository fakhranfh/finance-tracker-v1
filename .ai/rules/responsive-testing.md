---
paths:
  - 'resources/views/**'
  - 'resources/js/**'
  - 'resources/css/**'
---

# Responsive Testing

## Always test responsiveness in the browser
Any time a page or UI is added or changed (Blade, JS, CSS), verify its responsiveness in a real browser using the Claude in Chrome browser tools, in a **new tab** (`tabs_create_mcp`, never reuse an existing tab).

## Cover every breakpoint, mobile first
Resize the window (`resize_window`) and check the page from the smallest viewport up to desktop, e.g. 375 (mobile), 768 (tablet), 1024 (laptop), 1440 (desktop). Confirm no horizontal scroll, no overlapping/clipped elements, and that navigation, tables, forms, and modals stay usable at each size.

## Report
Mention which viewports were checked and any issues found or fixed before considering the change done.
