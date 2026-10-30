const PRESETS = {
    today: (now) => [now, now],
    this_month: (now) => [new Date(now.getFullYear(), now.getMonth(), 1), new Date(now.getFullYear(), now.getMonth() + 1, 0)],
    last_month: (now) => [new Date(now.getFullYear(), now.getMonth() - 1, 1), new Date(now.getFullYear(), now.getMonth(), 0)],
    last_30_days: (now) => [new Date(now.getFullYear(), now.getMonth(), now.getDate() - 29), now],
    this_year: (now) => [new Date(now.getFullYear(), 0, 1), new Date(now.getFullYear(), 11, 31)],
};

const pad = (part) => String(part).padStart(2, '0');
const toDateValue = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

/**
 * Period preset in the filter bar: picking one fills the date range filters, and editing
 * either date by hand drops the preset. The table reacts to the date inputs' own filter change.
 */
export function createPeriodPreset() {
    const select = document.getElementById('filter-period');
    const from = document.getElementById('filter-date_from');
    const to = document.getElementById('filter-date_to');

    const applyPreset = () => {
        const [start, end] = select.value ? PRESETS[select.value](new Date()) : [null, null];

        from.value = start ? toDateValue(start) : '';
        to.value = end ? toDateValue(end) : '';
    };

    // Runs on the select itself, so the dates are set before the table's delegated change handler reads them.
    select.addEventListener('change', applyPreset);

    // Default to today; must run before the table is created so its first load uses it.
    select.value = 'today';
    applyPreset();

    [from, to].forEach((input) => input.addEventListener('change', () => {
        select.value = '';
    }));
}
