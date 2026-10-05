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
 * Wires up a full server-driven table (load, search, sort, per-page,
 * pagination, selection, row/bulk delete, generate) from one config object.
 *
 * config:
 *  - ids:    { table, skeleton, wrapper, toolbar, generateButton, generateCount,
 *              confirmDelete, confirmBulkDelete }
 *  - flashKey, selectionKey, deleteModalId, bulkDeleteModalId
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
    const skeleton = document.getElementById(ids.skeleton);
    const wrapper = document.getElementById(ids.wrapper);
    const tableSkeleton = {
        show() {
            flash.hide();
            skeleton?.classList.remove('hidden');
            wrapper?.classList.add('hidden');
        },
        hide() {
            skeleton?.classList.add('hidden');
            wrapper?.classList.remove('hidden');
        },
    };
    const selection = new TableSelection(config.selectionKey);

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

    const tableSort = createTableSort({ root, onChange: reset });

    const perPage = createTablePerPage({
        selects: document.querySelectorAll('[data-per-page-select]'),
        onChange: reset,
    });

    const search = createTableSearch({
        input: toolbar?.querySelector('[data-table-search]'),
        onInput: () => selectAll.resetAllMatching(),
        onChange: reset,
    });

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => openModal(button.dataset.modalOpen));
    });

    load().then(() => flash.showStored());
}
