const MOBILE_QUERY = '(max-width: 639px)';

/**
 * Select-all behaviour for a data table: header checkbox, mobile select-all buttons,
 * "select all matching" / "clear selection" links and the bulk bar.
 *
 * @param {object} options
 * @param {HTMLElement} options.table tbody element carrying the data-label-* attributes
 * @param {import('./table-selection.js').TableSelection} options.selection
 * @param {() => number} options.getTotal total number of rows across all pages
 */
export function createTableSelectAll({ table, selection, getTotal }) {
    const tableEl = table?.closest('table');
    const bulkBar = tableEl?.querySelector('[data-table-bulk-row]');
    const selectedCountEl = bulkBar?.querySelector('[data-table-selected-count]');
    const selectAllMatchingButton = bulkBar?.querySelector('[data-table-select-all-matching]');
    const clearSelectionButton = bulkBar?.querySelector('[data-table-clear-selection]');
    const visibleCheckbox = tableEl?.querySelector('[data-table-select-all]');
    const mobileButtons = document.querySelectorAll('[data-table-select-all-mobile]');
    const mobileWrap = document.querySelector('[data-table-select-all-mobile-wrap]');
    const mobileQuery = window.matchMedia(MOBILE_QUERY);

    let allMatching = false;

    function label(key) {
        return table.dataset[key].replace(':total', getTotal());
    }

    function rowCheckboxes() {
        return [...(table?.querySelectorAll('[data-row-select]:not(:disabled)') ?? [])];
    }

    function resetAllMatching() {
        allMatching = false;
    }

    function syncSelectAllVisible() {
        if (!visibleCheckbox) {
            return;
        }

        const checkboxes = rowCheckboxes();

        visibleCheckbox.checked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);

        mobileButtons.forEach((button) => {
            const labelEl = button.querySelector('[data-button-label]');

            button.setAttribute('aria-pressed', String(visibleCheckbox.checked));

            if (labelEl) {
                labelEl.textContent = visibleCheckbox.checked
                    ? button.dataset.labelDeselect
                    : button.dataset.labelSelect;
            }
        });
    }

    function syncRowCheckboxes() {
        rowCheckboxes().forEach((checkbox) => {
            checkbox.checked = allMatching || selection.has(Number(checkbox.value));
        });

        syncSelectAllVisible();
    }

    function updateBulkBar() {
        if (!bulkBar || !selectedCountEl) {
            return;
        }

        if (selection.size === 0) {
            resetAllMatching();
            bulkBar.classList.add('hidden');
            mobileWrap?.classList.remove('hidden');
            return;
        }

        const total = getTotal();

        bulkBar.classList.remove('hidden');
        mobileWrap?.classList.add('hidden');
        selectedCountEl.textContent = allMatching ? total : selection.size;

        const canSelectAll =
            !allMatching && total > selection.size && (visibleCheckbox?.checked || mobileQuery.matches);

        selectAllMatchingButton?.classList.toggle('hidden', !canSelectAll);
        clearSelectionButton?.classList.toggle('hidden', !allMatching);

        if (selectAllMatchingButton) {
            selectAllMatchingButton.textContent = label('labelSelectAllMatching');
        }

        if (clearSelectionButton) {
            clearSelectionButton.textContent = table.dataset.labelClearSelection;
        }
    }

    /** Call from a row checkbox's change handler. */
    function onRowToggle() {
        resetAllMatching();
        syncSelectAllVisible();
        updateBulkBar();
    }

    mobileButtons.forEach((button) => button.addEventListener('click', () => visibleCheckbox?.click()));

    visibleCheckbox?.addEventListener('change', () => {
        selection.selectVisible(
            rowCheckboxes().map((checkbox) => Number(checkbox.value)),
            visibleCheckbox.checked,
        );
        resetAllMatching();
        syncRowCheckboxes();
        updateBulkBar();
    });

    selectAllMatchingButton?.addEventListener('click', () => {
        allMatching = true;
        updateBulkBar();
    });

    clearSelectionButton?.addEventListener('click', () => {
        selection.clear();
        resetAllMatching();
        syncRowCheckboxes();
        updateBulkBar();
    });

    return {
        isAllMatching: () => allMatching,
        resetAllMatching,
        onRowToggle,
        syncRowCheckboxes,
        updateBulkBar,
    };
}
