import { getAll, get, post } from '../shared/http.js';
import { closeModal, openModal } from '../shared/modal.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { formatDateTime } from '../shared/datetime.js';
import { hideSkeleton } from '../shared/loading-state.js';
import { wireCopyButtons } from '../shared/copy-to-clipboard.js';
import { routes } from '../routes.js';

const details = document.getElementById('webhook-details');

/** Read-only detail page of a webhook: fills the fields from the REST API. */
async function load() {
    const { webhookId, ...labels } = details.dataset;
    const { ok, data } = await get(routes.webhooks.show(webhookId));

    if (!ok) {
        hideSkeleton('webhook-details', 'webhook-show-skeleton');

        return;
    }

    const webhook = data.data;
    const field = (name) => details.querySelector(`[data-field="${name}"]`);
    const allLabel = details.dataset.allLabel;

    document.querySelector('[data-webhook-name]').textContent = webhook.name;
    details.querySelector('#token').value = webhook.token;
    details.querySelector('#url').value = webhook.url;
    field('status').textContent = webhook.is_active ? labels.activeLabel : labels.inactiveLabel;
    field('created_at').textContent = formatDateTime(webhook.created_at);
    field('allowed_ips').textContent = webhook.allowed_ips.length > 0 ? webhook.allowed_ips.join(', ') : allLabel;
    field('allowed_types').textContent = webhook.allowed_types.length > 0
        ? webhook.allowed_types.map((type) => labels[`type${type[0].toUpperCase()}${type.slice(1)}`] ?? type).join(', ')
        : allLabel;

    field('can_create_accounts').textContent = webhook.can_create_accounts ? labels.yesLabel : labels.noLabel;
    field('can_create_categories').textContent = webhook.can_create_categories ? labels.yesLabel : labels.noLabel;

    if (webhook.allowed_account_ids.length > 0) {
        const accounts = (await getAll(routes.accounts.index)) ?? [];
        const names = accounts.filter((account) => webhook.allowed_account_ids.includes(account.id)).map((account) => account.name);

        field('allowed_account_ids').textContent = names.length > 0 ? names.join(', ') : allLabel;
    } else {
        field('allowed_account_ids').textContent = allLabel;
    }

    hideSkeleton('webhook-details', 'webhook-show-skeleton');
}

/** Rotating replaces the token: reload so the page shows the new one. */
function wireRotateToken() {
    const confirmButton = document.getElementById('confirm-rotate-webhook-token');

    document.getElementById('rotate-webhook-token').addEventListener('click', () => openModal('rotate-webhook-token-modal'));

    confirmButton.addEventListener('click', async () => {
        setButtonLoading(confirmButton, true);

        try {
            const { ok } = await post(routes.webhooks.rotateToken(details.dataset.webhookId));

            if (ok) {
                window.location.reload();
            }
        } finally {
            setButtonLoading(confirmButton, false);
            closeModal('rotate-webhook-token-modal');
        }
    });
}

if (details) {
    wireCopyButtons(details);
    wireRotateToken();
    load();
}
