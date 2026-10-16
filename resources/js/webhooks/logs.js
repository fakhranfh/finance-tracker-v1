import { createTable } from '../shared/table/table.js';
import { formatDateTime } from '../shared/datetime.js';
import { del } from '../shared/http.js';
import { closeModal, openModal } from '../shared/modal.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { routes } from '../routes.js';

const MAX_PAYLOAD_LENGTH = 120;

const clearButton = document.getElementById('clear-webhook-logs');
const webhookId = clearButton?.dataset.webhookId;

/** Status code, colored by outcome. */
function statusNode(code) {
    const node = document.createElement('span');

    node.textContent = code;
    node.className = `font-medium ${code < 300 ? 'text-success' : 'text-error'}`;

    return node;
}

/** Compact one-line payload; the full JSON is in the tooltip. */
function payloadNode(payload) {
    const node = document.createElement('span');
    const json = payload ? JSON.stringify(payload) : '';

    node.className = 'font-mono text-body-sm break-all';
    node.title = payload ? JSON.stringify(payload, null, 2) : '';
    node.textContent = json.length > MAX_PAYLOAD_LENGTH ? `${json.slice(0, MAX_PAYLOAD_LENGTH)}…` : json || '-';

    return node;
}

if (clearButton && webhookId) {
    createTable({
        ids: {
            table: 'webhook-logs-table',
            wrapper: 'webhook-logs-table-wrapper',
            toolbar: 'webhook-logs-table-toolbar',
        },
        flashKey: 'webhook-logs-flash-success',
        selectionKey: `webhook-logs-table-selection-${webhookId}`,
        routes: {
            index: routes.webhooks.logs(webhookId),
        },
        row: (item) => ({
            cells: [
                formatDateTime(item.created_at),
                statusNode(item.status_code),
                item.message ?? '-',
                item.origin ?? '-',
                item.ip ?? '-',
                payloadNode(item.payload),
            ],
            readOnly: true,
            locked: true,
        }),
    });

    clearButton.addEventListener('click', () => openModal('clear-webhook-logs-modal'));

    document.getElementById('confirm-clear-webhook-logs').addEventListener('click', async (event) => {
        const button = event.currentTarget;

        setButtonLoading(button, true);

        try {
            const { ok } = await del(routes.webhooks.logs(webhookId));

            if (ok) {
                window.location.reload();
            }
        } finally {
            setButtonLoading(button, false);
            closeModal('clear-webhook-logs-modal');
        }
    });
}
