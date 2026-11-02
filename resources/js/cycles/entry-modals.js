import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { hideSnackbar } from '../shared/error-snackbar.js';
import { closeModal, openModal } from '../shared/modal.js';
import { setButtonLoading } from '../shared/loading-state.js';
import '../shared/money-input.js';
import { showError } from './flash.js';
import { today } from './stats.js';

/**
 * Wires the add/edit, finish and delete modals of cycle-entry-modals.blade.php.
 * The page supplies the API call for each action (`onSave`, `onFinish`, `onDelete`, each resolving to
 * the `{ ok, status, data }` of the request) and refreshes itself when it succeeds; the modals handle
 * validation, the loading state, server errors and closing.
 */
export function setupEntryModals({ labels, onSave, onFinish, onDelete, onBulkDelete }) {
    const entryForm = document.getElementById('cycle-entry-form');
    const finishForm = document.getElementById('cycle-finish-form');
    const deleteButton = document.getElementById('confirm-delete-cycle');
    let editing = null;
    let target = null;

    /** Clears old errors and runs native validation; returns false (and shows the errors) when it fails. */
    function passesNativeValidation(form) {
        clearFieldErrors(form);
        hideSnackbar();

        const errors = validateForm(form);

        if (Object.keys(errors).length) {
            showFieldErrors(form, errors);

            return false;
        }

        return true;
    }

    /** Runs an API call behind a button's loading state; shows server errors on the form and closes the modal on success. */
    async function run(form, modalId, firstField, action) {
        const button = form.querySelector('button[type="submit"]');

        setButtonLoading(button, true);

        try {
            const { ok, status, data } = await action();

            if (ok) {
                closeModal(modalId);
            } else if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? labels.error_generic, firstField);
            }
        } catch {
            showGenericError(form, labels.error_generic, firstField);
        } finally {
            setButtonLoading(button, false);
        }
    }

    entryForm.addEventListener('submit', (event) => {
        event.preventDefault();

        if (!passesNativeValidation(entryForm)) {
            return;
        }

        const name = entryForm.elements.namedItem('name').value.trim();
        const price = Number(entryForm.elements.namedItem('price').value);
        const boughtAt = entryForm.elements.namedItem('bought_at').value;

        if (!name) {
            showGenericError(entryForm, labels.error_invalid_name, 'name');
        } else if (!(price > 0)) {
            showGenericError(entryForm, labels.error_invalid_price, 'price');
        } else {
            run(entryForm, 'cycle-entry-modal', 'name', () => onSave({ name, price, bought_at: boughtAt }, editing));
        }
    });

    finishForm.addEventListener('submit', (event) => {
        event.preventDefault();

        if (passesNativeValidation(finishForm)) {
            run(finishForm, 'cycle-finish-modal', 'finished_at', () => onFinish(target, finishForm.elements.namedItem('finished_at').value));
        }
    });

    deleteButton.addEventListener('click', async () => {
        setButtonLoading(deleteButton, true);

        try {
            const { ok, data } = await onDelete(target);

            if (!ok) {
                showError(data.message ?? labels.error_generic);
            }
        } catch {
            showError(labels.error_generic);
        } finally {
            setButtonLoading(deleteButton, false);
            closeModal('cycle-delete-modal');
        }
    });

    const bulkDeleteButton = document.getElementById('confirm-bulk-delete-cycle');
    let bulkIds = [];

    bulkDeleteButton.addEventListener('click', async () => {
        setButtonLoading(bulkDeleteButton, true);

        try {
            const { ok, data } = await onBulkDelete(bulkIds);

            if (!ok) {
                showError(data.message ?? labels.error_generic);
            }
        } catch {
            showError(labels.error_generic);
        } finally {
            setButtonLoading(bulkDeleteButton, false);
            closeModal('cycle-bulk-delete-modal');
        }
    });

    function openForm(entry, defaults) {
        editing = entry;
        clearFieldErrors(entryForm);
        entryForm.elements.namedItem('name').value = entry?.name ?? defaults.name;
        entryForm.elements.namedItem('price').value = entry?.price ?? defaults.price;
        entryForm.elements.namedItem('bought_at').value = entry?.bought_at ?? today();
        document.getElementById('cycle-entry-modal-title').textContent = entry ? entryForm.dataset.editTitle : entryForm.dataset.addTitle;
        openModal('cycle-entry-modal');
    }

    return {
        /** `last` is the previous purchase of the type; its product and price pre-fill the form. */
        openAdd: (last) => openForm(null, { name: last?.name ?? '', price: last?.price ?? '' }),
        openEdit: (entry) => openForm(entry, {}),
        openFinish(entry) {
            target = entry;
            clearFieldErrors(finishForm);
            finishForm.elements.namedItem('finished_at').value = today();
            openModal('cycle-finish-modal');
        },
        openDelete(entry) {
            target = entry;
            openModal('cycle-delete-modal');
        },
        openBulkDelete(ids) {
            bulkIds = ids;
            openModal('cycle-bulk-delete-modal');
        },
    };
}
