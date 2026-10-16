import { createTable } from '../shared/table/table.js';
import { formatDateTime } from '../shared/datetime.js';
import { put } from '../shared/http.js';
import { routes } from '../routes.js';

/** Checkbox that activates or deactivates a webhook straight from the table. */
function activeToggle(item) {
    const label = document.createElement('label');
    label.className = 'inline-flex cursor-pointer items-center gap-2';

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.checked = Boolean(item.is_active);
    checkbox.setAttribute('data-webhook-active', '');
    checkbox.setAttribute('aria-label', item.name);
    checkbox.className =
        'h-4 w-4 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20';

    checkbox.addEventListener('change', async () => {
        const wanted = checkbox.checked;
        checkbox.disabled = true;

        try {
            const { ok } = await put(routes.webhooks.update(item.id), { name: item.name, is_active: wanted });

            if (!ok) {
                checkbox.checked = !wanted;
            }
        } catch {
            checkbox.checked = !wanted;
        } finally {
            checkbox.disabled = false;
        }
    });

    label.append(checkbox);

    return label;
}

const testLabel = document.getElementById('webhooks-table').dataset.labelTest;

createTable({
    ids: {
        table: 'webhooks-table',
        wrapper: 'webhooks-table-wrapper',
        toolbar: 'webhooks-table-toolbar',
        generateButton: 'generate-webhooks',
        generateCount: 'generate-count',
        confirmDelete: 'confirm-delete-webhook',
        confirmBulkDelete: 'confirm-bulk-delete-webhooks',
    },
    flashKey: 'webhooks-flash-success',
    selectionKey: 'webhooks-table-selection',
    deleteModalId: 'delete-webhook-modal',
    bulkDeleteModalId: 'bulk-delete-webhooks-modal',
    routes: {
        index: routes.webhooks.index,
        destroy: routes.webhooks.destroy,
        bulkDestroy: routes.webhooks.bulkDestroy,
        generate: routes.webhooks.generate,
    },
    row: (item) => ({
        cells: [
            item.name ?? '',
            activeToggle(item),
            formatDateTime(item.created_at),
        ],
        viewUrl: routes.pages.webhookShow(item.id),
        extraActions: [{ label: testLabel, url: `${routes.pages.webhookTest(item.id)}?from=index` }],
        editUrl: routes.pages.webhookEdit(item.id),
        locked: false,
    }),
});
