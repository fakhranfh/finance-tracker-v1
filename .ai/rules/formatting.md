---
paths:
  - 'resources/js/**'
  - 'resources/views/**'
---

# Currency and Date Formatting

## Money is always Rupiah
All money shown in the UI is Indonesian Rupiah: format it with `formatMoney()` from `resources/js/shared/money.js` (id-ID, IDR, e.g. "Rp 1.250.000"). Never hand-format amounts or show a bare number.

## Dates and times are "dd MMM yyyy, HH:mm"
All dates/times shown in the UI use `formatDateTime()` from `resources/js/shared/datetime.js` (24-hour, viewer timezone, month name in the page locale, e.g. "02 Okt 2026, 14:30"). Never call `toLocaleDateString()`/`toLocaleString()` directly.

## Timestamp inputs
Timestamp fields use `<input type="datetime-local">`: convert API values with `toDateTimeLocal()` and send them as ISO via `toISOString()`. The API returns ISO 8601.

## Keep the generator in sync
`make:manage` (`app/Console/Commands/MakeManage.php` and `stubs/manage/*.stub`) must use these helpers in the table and form code it generates.
