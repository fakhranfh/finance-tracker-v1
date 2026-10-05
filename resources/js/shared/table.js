import { get } from './http.js';
import { openModal } from './modal.js';
import { TableSelection } from './table-selection.js';
import { createTableSelectAll } from './table-select-all.js';
import { createTableRow } from './table-row.js';
import { renderPagination } from './table-pagination.js';
import { createTableSort } from './table-sort.js';
import { createGenerateButton } from './table-generate.js';
import { createTableFlash } from './table-flash.js';
import { createTableSearch } from './table-search.js';
import { createBulkDelete, createRowDelete } from './table-delete.js';
import { createTablePerPage } from './table-per-page.js';

/**
 * Multi-select dropdown inside the filter bar. Markup is marked up with:
 *  - data-filter-multi                 on the dropdown root (<details>)
 *  - data-filter-multi-summary         label; data-all-label and data-selected-template (":count")
 *  - data-filter-multi-search          optional live text search input
 *  - data-filter-multi-select-all      optional; checks the rows still visible after search
 *  - data-filter-multi-deselect-all    optional; clears every option
 *  - data-filter-multi-row             one per option, with data-search-text (lowercase)
 *  - checkboxes inside the rows        carry data-table-filter and name="<key>[]"
 */
export function createMultiSelectFilter(dropdown) {
    const summary = dropdown.querySelector('[data-filter-multi-summary]');
    const search = dropdown.querySelector('[data-filter-multi-search]');
    const selectAllButton = dropdown.querySelector('[data-filter-multi-select-all]');
    const deselectAllButton = dropdown.querySelector('[data-filter-multi-deselect-all]');

    const checkboxes = () => [...dropdown.querySelectorAll('input[type="checkbox"]')];
    const rows = () => [...dropdown.querySelectorAll('[data-filter-multi-row]')];

    function refreshSummary() {
        const selected = checkboxes().filter((checkbox) => checkbox.checked).length;

        summary.textContent = selected === 0
            ? summary.dataset.allLabel
            : summary.dataset.selectedTemplate.replace(':count', selected);
    }

    /** Sets checked state for the given boxes and fires one change event, so the table reloads once. */
    function setChecked(targets, checked) {
        const changed = targets.filter((checkbox) => checkbox.checked !== checked);

        changed.forEach((checkbox) => {
            checkbox.checked = checked;
        });

        changed[0]?.dispatchEvent(new Event('change', { bubbles: true }));
    }

    dropdown.addEventListener('change', refreshSummary);

    search?.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();

        rows().forEach((row) => {
            row.classList.toggle('hidden', query !== '' && !row.dataset.searchText.includes(query));
        });
    });

    selectAllButton?.addEventListener('click', () => {
        const visible = checkboxes().filter((checkbox) => !checkbox.closest('[data-filter-multi-row]').classList.contains('hidden'));

        setChecked(visible, true);
    });

    deselectAllButton?.addEventListener('click', () => {
        setChecked(checkboxes(), false);
    });

    refreshSummary();
}

/**
 * Wires up a full server-driven table (load, search, sort, per-page,
 * pagination, selection, row/bulk delete, generate) from one config object.
 *
 * config:
 *  - ids:    { table, wrapper, toolbar, generateButton, generateCount,
 *              confirmDelete, confirmBulkDelete }
 *  - flashKey, selectionKey, deleteModalId, bulkDeleteModalId
 *  - defaultSort: { key, direction } (optional initial sort)
 *  - routes: { index, destroy, bulkDestroy, generate }
 *  - row(item, { lockedId }) => { cells, editUrl, locked }
 */
export function createTable(config) {
    const { ids, routes } = config;

    const table = document.getElementById(ids.table);

    if (!table) {
        return;
    }

    const tbody = table;
    const emptyRow = tbody.querySelector('[data-table-empty-row]');
    const paginations = document.querySelectorAll('[data-pagination]');
    const toolbar = document.getElementById(ids.toolbar);
    const root = table.closest('table');
    const lockedId = Number(tbody.dataset.lockedId);

    const flash = createTableFlash({ storageKey: config.flashKey });
    const wrapper = document.getElementById(ids.wrapper);
    // Rendered by the table component, right after the filters that stay visible while loading.
    const skeleton = wrapper?.querySelector('[data-table-skeleton]');

    const wrapperContent = () => [...(wrapper?.children ?? [])]
        .filter((el) => el !== skeleton && !el.matches('[data-table-filters]'));
    const tableSkeleton = {
        show() {
            flash.hide();
            skeleton?.classList.remove('hidden');
            wrapper?.classList.remove('hidden');
            wrapperContent().forEach((el) => el.classList.add('hidden'));
        },
        hide() {
            skeleton?.classList.add('hidden');
            wrapperContent().forEach((el) => el.classList.remove('hidden'));
        },
    };
    const selection = new TableSelection(config.selectionKey);
    const totalEls = [...document.querySelectorAll('[data-table-total]')];

    const filterInputs = [...(document.getElementById(config.filtersId)?.querySelectorAll('[data-table-filter]') ?? [])];
    const filterKey = (input) => input.name.replace(/\[\]$/, '');

    const activeFilters = () => {
        const filters = {};

        filterInputs.forEach((input) => {
            const key = filterKey(input);

            if (input.type === 'checkbox') {
                if (input.checked) {
                    filters[key] = [...(filters[key] ?? []), input.value];
                }
            } else if (input.value) {
                filters[key] = input.value;
            }
        });

        return filters;
    };

    let currentUrl = routes.index;
    let total = 0;

    const selectAll = createTableSelectAll({ table, selection, getTotal: () => total });
    const rowRenderer = createTableRow({ tbody, selection, selectAll });

    const renderRow = (item) => {
        const { cells, editUrl, locked } = config.row(item, { lockedId });

        return rowRenderer.render({
            id: item.id,
            cells,
            editUrl,
            onDelete: () => rowDelete.request(item.id),
            locked,
        });
    };

    async function load(url = currentUrl) {
        tableSkeleton.show();

        const requestUrl = new URL(url, window.location.origin);

        search.applyToUrl(requestUrl);
        perPage.applyToUrl(requestUrl);
        tableSort.applyToUrl(requestUrl);

        filterInputs.forEach((input) => {
            requestUrl.searchParams.delete(filterKey(input));
            requestUrl.searchParams.delete(`${filterKey(input)}[]`);
        });
        Object.entries(activeFilters()).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((item) => requestUrl.searchParams.append(`${key}[]`, item));
            } else {
                requestUrl.searchParams.set(key, value);
            }
        });

        const { ok, data } = await get(requestUrl.toString());

        if (!ok) {
            tableSkeleton.hide();
            return;
        }

        currentUrl = requestUrl.toString();

        tbody
            .querySelectorAll(`tr:not([data-table-empty-row]):not([data-table-bulk-row])`)
            .forEach((row) => row.remove());

        if (data.data.length === 0) {
            emptyRow?.classList.remove('hidden');
        } else {
            emptyRow?.classList.add('hidden');
            data.data.forEach((item) => tbody.appendChild(renderRow(item)));
        }

        total = data.deletable_total ?? data.meta?.total ?? data.data.length;

        totalEls.forEach((el) => {
            el.textContent = el.dataset.labelTotal.replace(':total', total);
        });

        renderPagination(paginations, data.meta, load);
        tableSort.updateIcons();
        selectAll.syncRowCheckboxes();
        selectAll.updateBulkBar();

        tableSkeleton.hide();
    }

    const reload = () => load();
    const reset = () => load(routes.index);

    const rowDelete = createRowDelete({
        modalId: config.deleteModalId,
        confirmButton: document.getElementById(ids.confirmDelete),
        destroyUrl: routes.destroy,
        selection,
        onDeleted: reload,
    });

    createBulkDelete({
        modalId: config.bulkDeleteModalId,
        confirmButton: document.getElementById(ids.confirmBulkDelete),
        triggerButton: root?.querySelector('[data-table-bulk-delete]'),
        bulkUrl: routes.bulkDestroy,
        selection,
        selectAll,
        getSearch: () => search.value,
        getFilters: activeFilters,
        onDeleted: reload,
    });

    createGenerateButton({
        button: document.getElementById(ids.generateButton),
        countInput: document.getElementById(ids.generateCount),
        url: routes.generate,
        getPayload: config.generatePayload,
        onGenerated: async (message) => {
            flash.show(message);
            await load();
        },
    });

    const tableSort = createTableSort({ root, onChange: reset, defaultSort: config.defaultSort });

    const perPage = createTablePerPage({
        selects: document.querySelectorAll('[data-per-page-select]'),
        onChange: reset,
    });

    const search = createTableSearch({
        input: toolbar?.querySelector('[data-table-search]'),
        onInput: () => selectAll.resetAllMatching(),
        onChange: reset,
    });

    const filtersRoot = document.getElementById(config.filtersId);
    const filtersSlot = document.querySelector('[data-table-filters-slot]');
    const filtersFooterSlot = document.querySelector('[data-table-filters-footer-slot]');
    // Grab the clear button before the placement below may move it out of filtersRoot.
    const clearButton = filtersRoot?.querySelector('[data-table-filter-clear]');

    // On mobile the filter fields live in the filter modal and the clear button moves to its sticky footer;
    // on desktop both go back to their place in the table.
    if (filtersRoot && filtersSlot && filtersFooterSlot) {
        const mobileQuery = window.matchMedia('(max-width: 639px)');
        const filtersAnchor = document.createComment('table-filters');
        const clearWrap = filtersRoot.querySelector('[data-table-filter-clear-wrap]');
        const clearAnchor = document.createComment('table-filter-clear');

        filtersRoot.before(filtersAnchor);
        clearWrap?.before(clearAnchor);

        const placeFilters = () => {
            if (mobileQuery.matches) {
                filtersSlot.append(filtersRoot);
                filtersFooterSlot.append(clearWrap);
            } else {
                filtersAnchor.after(filtersRoot);
                clearAnchor.after(clearWrap);
            }
        };

        placeFilters();
        mobileQuery.addEventListener('change', placeFilters);
    }

    filtersRoot?.addEventListener('change', (event) => {
        if (!event.target.matches('[data-table-filter]')) {
            return;
        }

        selectAll.resetAllMatching();
        reset();
    });

    filtersRoot?.querySelectorAll('[data-filter-multi]').forEach(createMultiSelectFilter);

    clearButton?.addEventListener('click', () => {
        const hadValue = filterInputs.some((input) => (input.type === 'checkbox' ? input.checked : input.value !== ''));

        filterInputs.forEach((input) => {
            if (input.type === 'checkbox') {
                input.checked = false;
            } else {
                input.value = '';
            }
        });

        filtersRoot.querySelectorAll('[data-filter-multi-search]').forEach((search) => {
            search.value = '';
            search.dispatchEvent(new Event('input'));
        });

        // Refresh each dropdown's summary label; the dropdown listens on itself, not on the bar.
        filtersRoot.querySelectorAll('[data-filter-multi]').forEach((dropdown) => {
            dropdown.dispatchEvent(new Event('change'));
        });

        if (hadValue) {
            selectAll.resetAllMatching();
            reset();
        }
    });

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => openModal(button.dataset.modalOpen));
    });

    load().then(() => flash.showStored());
}
