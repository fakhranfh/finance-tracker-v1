import { del } from './http.js';
import { closeModal, openModal } from './modal.js';
import { setButtonLoading } from './loading-state.js';

/**
 * Single-row delete: `request(id)` opens the confirm modal, the confirm
 * button deletes it, then `onDeleted()` fires.
 */
export function createRowDelete({ modalId, confirmButton, destroyUrl, selection, onDeleted }) {
    let pendingId = null;

    confirmButton?.addEventListener('click', async () => {
        if (!pendingId) {
            return;
        }

        const deletedId = pendingId;

        setButtonLoading(confirmButton, true);

        const { ok } = await del(destroyUrl(deletedId));

        setButtonLoading(confirmButton, false);
        closeModal(modalId);
        pendingId = null;

        if (ok) {
            selection.toggle(deletedId, false);
            await onDeleted();
        }
    });

    return {
        request(id) {
            pendingId = id;
            openModal(modalId);
        },
    };
}

/**
 * Bulk delete of the selected rows (or every row matching the search when
 * "select all matching" is active).
 */
export function createBulkDelete({ modalId, confirmButton, triggerButton, bulkUrl, selection, selectAll, getSearch, getFilters = () => ({}), onDeleted }) {
    triggerButton?.addEventListener('click', () => {
        if (selection.size > 0) {
            openModal(modalId);
        }
    });

    confirmButton?.addEventListener('click', async () => {
        const ids = selection.selectedIds;

        if (ids.length === 0) {
            closeModal(modalId);
            return;
        }

        const payload = selectAll.isAllMatching() ? { all: true, search: getSearch() || null, ...getFilters() } : { ids };
        setButtonLoading(confirmButton, true);

        const { ok } = await del(bulkUrl, payload);

        setButtonLoading(confirmButton, false);
        closeModal(modalId);

        if (ok) {
            selection.clear();
            selectAll.resetAllMatching();
            await onDeleted();
        }
    });
}
