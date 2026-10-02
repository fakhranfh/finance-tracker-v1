/**
 * "Items per page" selects ([data-per-page-select]); keeps every select in
 * sync and calls `onChange()` after a change.
 */
export function createTablePerPage({ selects, onChange }) {
    let value = Number(selects[0]?.value) || 10;

    selects.forEach((select) => {
        select.addEventListener('change', () => {
            value = Number(select.value);
            selects.forEach((other) => (other.value = select.value));
            onChange();
        });
    });

    return {
        applyToUrl(url) {
            url.searchParams.set('per_page', value);
        },
    };
}
